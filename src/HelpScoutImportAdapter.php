<?php

namespace Escalated\Plugins\ImportHelpScout;

use Escalated\Laravel\Contracts\ImportAdapter;
use Escalated\Laravel\Models\ImportSourceMap;
use Escalated\Laravel\Support\ExtractResult;

class HelpScoutImportAdapter implements ImportAdapter
{
    private array $collectedAttachments = [];
    private ?string $currentJobId = null;

    /** Set by the framework before calling extract() — needed for reply iteration */
    public function setJobId(string $jobId): void
    {
        $this->currentJobId = $jobId;
    }

    public function name(): string
    {
        return 'helpscout';
    }

    public function displayName(): string
    {
        return 'Help Scout';
    }

    public function credentialFields(): array
    {
        return [
            ['name' => 'app_id', 'label' => 'OAuth App ID', 'type' => 'text', 'help' => 'OAuth App ID from Help Scout → My Apps'],
            ['name' => 'app_secret', 'label' => 'OAuth App Secret', 'type' => 'password', 'help' => 'OAuth App Secret from Help Scout → My Apps'],
        ];
    }

    public function testConnection(array $credentials): bool
    {
        return HelpScoutClient::fromCredentials($credentials)->testConnection();
    }

    public function entityTypes(): array
    {
        return ['agents', 'tags', 'departments', 'contacts', 'tickets', 'replies', 'attachments'];
    }

    public function defaultFieldMappings(string $entityType): array
    {
        return match ($entityType) {
            'tickets' => [
                'subject' => 'title',
                'status' => 'status',
                'assignee' => 'assigned_to',
                'primaryCustomer' => 'requester',
                'mailboxId' => 'department',
                'tags' => 'tags',
            ],
            default => [],
        };
    }

    public function availableSourceFields(string $entityType, array $credentials): array
    {
        return match ($entityType) {
            'tickets' => [
                ['name' => 'subject', 'label' => 'Subject', 'escalated_options' => ['title']],
                ['name' => 'status', 'label' => 'Status', 'escalated_options' => ['status']],
                ['name' => 'assignee', 'label' => 'Assignee', 'escalated_options' => ['assigned_to']],
                ['name' => 'primaryCustomer', 'label' => 'Customer', 'escalated_options' => ['requester']],
                ['name' => 'mailboxId', 'label' => 'Mailbox', 'escalated_options' => ['department']],
                ['name' => 'tags', 'label' => 'Tags', 'escalated_options' => ['tags']],
            ],
            default => [],
        };
    }

    public function extract(string $entityType, array $credentials, ?string $cursor): ExtractResult
    {
        $client = HelpScoutClient::fromCredentials($credentials);

        return match ($entityType) {
            'agents' => $this->extractAgents($client, $cursor),
            'tags' => $this->extractTags($client, $cursor),
            'departments' => $this->extractDepartments($client, $cursor),
            'contacts' => $this->extractContacts($client, $cursor),
            'tickets' => $this->extractTickets($client, $cursor),
            'replies' => $this->extractReplies($client, $cursor),
            'attachments' => $this->extractAttachments($client, $cursor),
            default => new ExtractResult([], null),
        };
    }

    private function extractAgents(HelpScoutClient $client, ?string $cursor): ExtractResult
    {
        $page = $cursor !== null ? (int) substr($cursor, 5) : 1; // cursor format: "page:N"

        $data = $client->get('users', ['page' => $page, 'pageSize' => 50]);

        $embedded = $data['_embedded']['users'] ?? [];
        $records = array_map(
            [HelpScoutFieldMapper::class, 'normalizeUser'],
            $embedded,
        );

        $nextCursor = isset($data['_links']['next']['href']) ? 'page:' . ($page + 1) : null;
        $total = $data['page']['totalElements'] ?? null;

        return new ExtractResult($records, $nextCursor, $total);
    }

    private function extractTags(HelpScoutClient $client, ?string $cursor): ExtractResult
    {
        $page = $cursor !== null ? (int) substr($cursor, 5) : 1;

        $data = $client->get('tags', ['page' => $page, 'pageSize' => 50]);

        $embedded = $data['_embedded']['tags'] ?? [];
        $records = array_map(
            fn ($tag) => HelpScoutFieldMapper::normalizeTag($tag['name'] ?? $tag['tag'] ?? ''),
            $embedded,
        );

        $nextCursor = isset($data['_links']['next']['href']) ? 'page:' . ($page + 1) : null;
        $total = $data['page']['totalElements'] ?? null;

        return new ExtractResult($records, $nextCursor, $total);
    }

    private function extractDepartments(HelpScoutClient $client, ?string $cursor): ExtractResult
    {
        $page = $cursor !== null ? (int) substr($cursor, 5) : 1;

        $data = $client->get('mailboxes', ['page' => $page, 'pageSize' => 50]);

        $embedded = $data['_embedded']['mailboxes'] ?? [];
        $records = array_map(
            [HelpScoutFieldMapper::class, 'normalizeMailbox'],
            $embedded,
        );

        $nextCursor = isset($data['_links']['next']['href']) ? 'page:' . ($page + 1) : null;
        $total = $data['page']['totalElements'] ?? null;

        return new ExtractResult($records, $nextCursor, $total);
    }

    private function extractContacts(HelpScoutClient $client, ?string $cursor): ExtractResult
    {
        $page = $cursor !== null ? (int) substr($cursor, 5) : 1;

        $data = $client->get('customers', ['page' => $page, 'pageSize' => 50]);

        $embedded = $data['_embedded']['customers'] ?? [];
        $records = array_map(
            [HelpScoutFieldMapper::class, 'normalizeCustomer'],
            $embedded,
        );

        $nextCursor = isset($data['_links']['next']['href']) ? 'page:' . ($page + 1) : null;
        $total = $data['page']['totalElements'] ?? null;

        return new ExtractResult($records, $nextCursor, $total);
    }

    private function extractTickets(HelpScoutClient $client, ?string $cursor): ExtractResult
    {
        $page = $cursor !== null ? (int) substr($cursor, 5) : 1;

        // Conversations are paginated at 25 per page
        $data = $client->get('conversations', ['page' => $page, 'pageSize' => 25]);

        $embedded = $data['_embedded']['conversations'] ?? [];
        $records = array_map(
            [HelpScoutFieldMapper::class, 'normalizeConversation'],
            $embedded,
        );

        $nextCursor = isset($data['_links']['next']['href']) ? 'page:' . ($page + 1) : null;
        $total = $data['page']['totalElements'] ?? null;

        return new ExtractResult($records, $nextCursor, $total);
    }

    /**
     * Extract replies by iterating through all imported conversations.
     *
     * Cursor format: "idx:N" where N is the offset into the source map.
     * Threads per conversation are fetched in a single request (max 100).
     */
    private function extractReplies(HelpScoutClient $client, ?string $cursor): ExtractResult
    {
        $offset = 0;
        if ($cursor !== null && str_starts_with($cursor, 'idx:')) {
            $offset = (int) substr($cursor, 4);
        }

        // Query source maps for the next conversation to fetch threads for
        $conversationMap = ImportSourceMap::where('import_job_id', $this->currentJobId ?? '')
            ->where('entity_type', 'tickets')
            ->orderBy('id')
            ->offset($offset)
            ->first();

        if (! $conversationMap) {
            return new ExtractResult([], null); // All conversations processed
        }

        $conversationId = $conversationMap->source_id;

        // Help Scout threads endpoint returns up to 100 threads per conversation
        $data = $client->get("conversations/{$conversationId}/threads");

        $records = $this->normalizeThreads($data, $conversationId);

        // Move to the next conversation
        $nextCursor = 'idx:' . ($offset + 1);

        return new ExtractResult($records, $nextCursor);
    }

    private function normalizeThreads(array $data, string $conversationId): array
    {
        $records = [];
        foreach ($data['_embedded']['threads'] ?? [] as $thread) {
            $records[] = HelpScoutFieldMapper::normalizeThread($thread, $conversationId);

            // Collect attachments embedded in threads
            foreach ($thread['attachments'] ?? [] as $attachment) {
                $this->collectedAttachments[] = HelpScoutFieldMapper::normalizeAttachment(
                    $attachment, 'reply', (string) $thread['id']
                );
            }
        }
        return $records;
    }

    /**
     * Extract attachments collected during reply extraction.
     * Returns all attachment metadata; actual download is handled by the framework.
     */
    private function extractAttachments(HelpScoutClient $client, ?string $cursor): ExtractResult
    {
        if ($cursor !== null) {
            return new ExtractResult([], null); // Already returned all in first call
        }

        $records = $this->collectedAttachments;
        $this->collectedAttachments = [];

        return new ExtractResult($records, null, count($records));
    }
}
