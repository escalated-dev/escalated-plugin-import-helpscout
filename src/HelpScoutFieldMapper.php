<?php

namespace Escalated\Plugins\ImportHelpScout;

class HelpScoutFieldMapper
{
    public static function statusMap(): array
    {
        return [
            'active' => 'open',
            'pending' => 'waiting_on_customer',
            'closed' => 'closed',
            'spam' => 'closed',
        ];
    }

    public static function mapStatus(?string $status): string
    {
        return static::statusMap()[$status ?? 'active'] ?? 'open';
    }

    /**
     * Help Scout has no priority field — always default to medium.
     */
    public static function mapPriority(): string
    {
        return 'medium';
    }

    /**
     * Normalize a Help Scout conversation into the standard import format.
     */
    public static function normalizeConversation(array $conversation): array
    {
        $assigneeId = $conversation['assignee']['id'] ?? null;
        $mailboxId = $conversation['mailboxId'] ?? null;
        $customerId = $conversation['primaryCustomer']['id'] ?? null;

        return [
            'source_id' => (string) $conversation['id'],
            'title' => $conversation['subject'] ?? 'No subject',
            'status' => static::mapStatus($conversation['status'] ?? null),
            'priority' => static::mapPriority(),
            'requester_source_id' => $customerId !== null ? (string) $customerId : '',
            'assignee_source_id' => $assigneeId !== null ? (string) $assigneeId : '',
            'department_source_id' => $mailboxId !== null ? (string) $mailboxId : '',
            'tag_source_ids' => array_column($conversation['tags'] ?? [], 'tag'),
            'metadata' => [
                'helpscout_id' => $conversation['id'],
                'helpscout_number' => $conversation['number'] ?? null,
            ],
            'created_at' => $conversation['createdAt'] ?? null,
            'updated_at' => $conversation['userUpdatedAt'] ?? $conversation['createdAt'] ?? null,
        ];
    }

    public static function normalizeCustomer(array $customer): array
    {
        // Help Scout returns emails as an array of {value, type} objects
        $emails = $customer['emails'] ?? [];
        $primaryEmail = '';
        foreach ($emails as $emailEntry) {
            if (($emailEntry['type'] ?? '') === 'work' || $primaryEmail === '') {
                $primaryEmail = $emailEntry['value'] ?? '';
            }
        }

        $firstName = $customer['firstName'] ?? '';
        $lastName = $customer['lastName'] ?? '';
        $name = trim("{$firstName} {$lastName}") ?: ($customer['email'] ?? $primaryEmail);

        return [
            'source_id' => (string) $customer['id'],
            'name' => $name,
            'email' => $primaryEmail,
        ];
    }

    public static function normalizeUser(array $user): array
    {
        return [
            'source_id' => (string) $user['id'],
            'name' => trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? '')),
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'user',
        ];
    }

    public static function normalizeMailbox(array $mailbox): array
    {
        return [
            'source_id' => (string) $mailbox['id'],
            'name' => $mailbox['name'] ?? 'Unknown',
        ];
    }

    /**
     * Normalize a Help Scout thread (reply or note) into the standard import format.
     *
     * Thread types: customer, message, note, lineitem, phone, chat, reassign, move, forward
     */
    public static function normalizeThread(array $thread, string $conversationSourceId): array
    {
        $threadType = $thread['type'] ?? 'message';
        $isInternalNote = ($threadType === 'note');

        // Author can be a customer or a user
        $authorId = $thread['author']['id'] ?? null;

        return [
            'source_id' => (string) $thread['id'],
            'ticket_source_id' => $conversationSourceId,
            'body' => $thread['body'] ?? '',
            'is_internal_note' => $isInternalNote,
            'author_source_id' => $authorId !== null ? (string) $authorId : '',
            'thread_type' => $threadType,
            'created_at' => $thread['createdAt'] ?? null,
            'updated_at' => $thread['createdAt'] ?? null,
        ];
    }

    public static function normalizeTag(string $tagName): array
    {
        return [
            'source_id' => $tagName,
            'name' => $tagName,
        ];
    }

    public static function normalizeAttachment(array $attachment, string $parentType, string $parentSourceId): array
    {
        return [
            'source_id' => (string) $attachment['id'],
            'parent_type' => $parentType,
            'parent_source_id' => $parentSourceId,
            'filename' => $attachment['filename'] ?? 'unknown',
            'mime_type' => $attachment['mimeType'] ?? 'application/octet-stream',
            'size' => $attachment['size'] ?? 0,
            'download_url' => $attachment['_links']['data']['href'] ?? '',
        ];
    }
}
