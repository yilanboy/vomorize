<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PurgeCdnCache
{
    /**
     * Purge Cloudflare CDN cache for a list of vocabulary IDs by directory prefix.
     *
     * @param  array<int, int|string>  $vocabularyIds
     *
     * @throws RuntimeException
     */
    public function purgeVocabularyAudio(array $vocabularyIds): void
    {
        if (empty($vocabularyIds)) {
            return;
        }

        $baseUrl = (string) (config('filesystems.disks.r2.url') ?: 'https://assets.vomorize.com');
        $host = (string) parse_url($baseUrl, PHP_URL_HOST);

        if ($host === '') {
            $host = preg_replace('#^https?://#', '', rtrim($baseUrl, '/')) ?? 'assets.vomorize.com';
        }

        $prefixes = array_map(
            fn (int|string $id): string => "{$host}/vocabulary/{$id}/",
            $vocabularyIds,
        );

        $this->purgePrefixes($prefixes);
    }

    /**
     * Purge Cloudflare CDN cache by URL prefixes.
     * Cloudflare allows up to 100 prefixes per API request.
     *
     * @param  array<int, string>  $prefixes
     *
     * @throws RuntimeException
     */
    public function purgePrefixes(array $prefixes): void
    {
        $uniquePrefixes = array_values(array_unique($prefixes));

        if (empty($uniquePrefixes)) {
            return;
        }

        [$key, $zoneId] = $this->getCredentials();

        foreach (array_chunk($uniquePrefixes, 100) as $chunk) {
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
     * Purge Cloudflare CDN cache by single file URLs.
     * Cloudflare allows up to 30 files per API request.
     *
     * @param  array<int, string>  $urls
     *
     * @throws RuntimeException
     */
    public function purgeFiles(array $urls): void
    {
        $uniqueUrls = array_values(array_unique($urls));

        if (empty($uniqueUrls)) {
            return;
        }

        [$key, $zoneId] = $this->getCredentials();

        foreach (array_chunk($uniqueUrls, 30) as $chunk) {
            $response = Http::withToken($key)
                ->asJson()
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                    'files' => $chunk,
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
    protected function getCredentials(): array
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
     * @param  Response  $response
     *
     * @throws RuntimeException
     */
    protected function handleFailure($response): never
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

        $summary = ! empty($messages) ? implode('; ', $messages) : $response->body();

        throw new RuntimeException("Cloudflare CDN purge failed (Status {$response->status()}): {$summary}");
    }
}
