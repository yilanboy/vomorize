<?php

declare(strict_types=1);

namespace App\Actions;

use App\Services\CloudflareCdnService;
use RuntimeException;

readonly class PurgeVocabularyAudioCache
{
    public function __construct(
        private CloudflareCdnService $cloudflareCdnService,
    ) {}

    /**
     * Purge Cloudflare CDN cache for a list of vocabulary IDs by directory prefix.
     *
     * @param  array<int, int|string>  $vocabularyIds
     *
     * @throws RuntimeException
     */
    public function handle(array $vocabularyIds): void
    {
        if ($vocabularyIds === []) {
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

        $this->cloudflareCdnService->purge($prefixes);
    }
}
