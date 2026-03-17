<?php

namespace Escalated\Plugins\ImportHelpScout;

use Illuminate\Support\Facades\Http;

class HelpScoutClient
{
    private const BASE_URL = 'https://api.helpscout.net/v2';
    private const TOKEN_URL = 'https://api.helpscout.net/v2/oauth2/token';

    private string $appId;
    private string $appSecret;
    private ?string $accessToken = null;
    private int $tokenExpiresAt = 0;

    public function __construct(string $appId, string $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    public static function fromCredentials(array $credentials): static
    {
        return new static(
            $credentials['app_id'],
            $credentials['app_secret'],
        );
    }

    /**
     * Obtain or refresh the OAuth 2.0 Client Credentials bearer token.
     */
    private function obtainToken(): void
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'client_credentials',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Help Scout OAuth token request failed (' . $response->status() . '): ' . $response->body()
            );
        }

        $body = $response->json();
        $this->accessToken = $body['access_token'];
        // Buffer 60 seconds off the expiry to avoid using a token at the last moment
        $this->tokenExpiresAt = time() + ($body['expires_in'] ?? 7200) - 60;
    }

    /**
     * Return the current bearer token, obtaining or refreshing it as needed.
     */
    private function token(): string
    {
        if ($this->accessToken === null || time() >= $this->tokenExpiresAt) {
            $this->obtainToken();
        }

        return $this->accessToken;
    }

    /**
     * Make an authenticated GET request with rate limit handling and 301 redirect following.
     */
    public function get(string $endpoint, array $query = []): array
    {
        $url = str_starts_with($endpoint, 'http') ? $endpoint : self::BASE_URL . '/' . ltrim($endpoint, '/');

        return $this->request($url, $query);
    }

    public function testConnection(): bool
    {
        $response = $this->get('users/me');
        return isset($response['id']);
    }

    private function request(string $url, array $query = [], int $retries = 3): array
    {
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token(),
                'Accept' => 'application/json',
            ])->timeout(30)->withOptions(['allow_redirects' => true])->get($url, $query);

            // 301 redirect for merged conversations — Laravel Http follows redirects automatically,
            // but flag it here for visibility. The allow_redirects option above handles it.
            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 60);
                sleep(min($retryAfter, 120));
                continue;
            }

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            if ($response->status() >= 500 && $attempt < $retries) {
                sleep(2 ** $attempt);
                continue;
            }

            throw new \RuntimeException(
                'Help Scout API error (' . $response->status() . '): ' . $response->body()
            );
        }

        throw new \RuntimeException('Help Scout API request failed after retries.');
    }
}
