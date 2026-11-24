<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

readonly class CloudflareCdnService
{
    private const int MAX_PREFIXES_PER_REQUEST = 100;

    /**
     * Purge Cloudflare CDN cache by URL prefixes.
     * Cloudflare allows up to 100 prefixes per API request.
     *
     * @param  array<int, string>  $prefixes
     *
     * @throws RuntimeException
     */
    public function purge(array $prefixes): void
    {
        $uniquePrefixes = array_values(array_unique($prefixes));

        if ($uniquePrefixes === []) {
            return;
        }

        [$key, $zoneId] = $this->getCredentials();

        foreach (array_chunk($uniquePrefixes, self::MAX_PREFIXES_PER_REQUEST) as $chunk) {
            $response = Http::withToken($key)
                ->asJson()
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                    'prefixes' => $chunk,
                ]);

            if (! $response->successful() || ! ($response->json('success') ?? false)) {
                $this->handleFailure($response);
            }
        }
    }

    /**
     * Retrieve and validate Cloudflare credentials.
     *
     * @return array{0: string, 1: string}
     *
     * @throws RuntimeException
     */
    private function getCredentials(): array
    {
        $key = config('services.cloudflare.key');
        $zoneId = config('services.cloudflare.zone_id');

        if (empty($key)) {
            throw new RuntimeException('Cloudflare API key is not configured (CLOUDFLARE_KEY).');
        }

        if (empty($zoneId)) {
            throw new RuntimeException('Cloudflare Zone ID is not configured (CLOUDFLARE_ZONE_ID).');
        }

        return [(string) $key, (string) $zoneId];
    }

    /**
     * Extract error details and throw a RuntimeException.
     *
     * @throws RuntimeException
     */
    private function handleFailure(Response $response): never
    {
        /** @var array<int, array<string, mixed>>|null $errors */
        $errors = $response->json('errors');
        $messages = [];

        if (is_array($errors)) {
            foreach ($errors as $error) {
                if (isset($error['message']) && is_string($error['message'])) {
                    $messages[] = $error['message'];
                }
            }
        }

        $summary = $messages !== [] ? implode('; ', $messages) : $response->body();

        throw new RuntimeException("Cloudflare CDN purge failed (Status {$response->status()}): {$summary}");
    }
}
