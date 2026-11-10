<?php

declare(strict_types=1);

use App\Actions\PurgeCdnCache;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.cloudflare.key', 'test_key');
    Config::set('services.cloudflare.zone_id', 'test_zone_id');
    Config::set('filesystems.disks.r2.url', 'https://assets.vomorize.com');
});

it('throws an exception when cloudflare api key is missing', function () {
    Config::set('services.cloudflare.key', null);

    expect(fn () => app(PurgeCdnCache::class)->purgeVocabularyAudio([1]))
        ->toThrow(RuntimeException::class, 'Cloudflare API key is not configured');
});

it('throws an exception when cloudflare zone id is missing', function () {
    Config::set('services.cloudflare.zone_id', null);

    expect(fn () => app(PurgeCdnCache::class)->purgeVocabularyAudio([1]))
        ->toThrow(RuntimeException::class, 'Cloudflare Zone ID is not configured');
});

it('does nothing when vocabulary ids array is empty', function () {
    Http::fake();

    app(PurgeCdnCache::class)->purgeVocabularyAudio([]);

    Http::assertNothingSent();
});

it('purges vocabulary audio by prefix with correct payload and headers', function () {
    Http::fake([
        'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache' => Http::response([
            'success' => true,
            'errors' => [],
            'result' => ['id' => 'test_zone_id'],
        ], 200),
    ]);

    app(PurgeCdnCache::class)->purgeVocabularyAudio([1, 2]);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache'
            && $request->hasHeader('Authorization', 'Bearer test_key')
            && $request['prefixes'] === [
                'assets.vomorize.com/vocabulary/1/',
                'assets.vomorize.com/vocabulary/2/',
            ];
    });
});

it('chunks prefixes into batches of 100 when purging prefixes', function () {
    Http::fake([
        'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache' => Http::response([
            'success' => true,
            'errors' => [],
            'result' => ['id' => 'test_zone_id'],
        ], 200),
    ]);

    $ids = range(1, 150);
    app(PurgeCdnCache::class)->purgeVocabularyAudio($ids);

    Http::assertSentCount(2);

    Http::assertSent(function (Request $request) {
        return count($request['prefixes']) === 100;
    });

    Http::assertSent(function (Request $request) {
        return count($request['prefixes']) === 50;
    });
});

it('chunks files into batches of 30 when purging files', function () {
    Http::fake([
        'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache' => Http::response([
            'success' => true,
            'errors' => [],
            'result' => ['id' => 'test_zone_id'],
        ], 200),
    ]);

    $urls = array_map(fn (int $i) => "https://assets.vomorize.com/file-{$i}.mp3", range(1, 45));
    app(PurgeCdnCache::class)->purgeFiles($urls);

    Http::assertSentCount(2);

    Http::assertSent(function (Request $request) {
        return count($request['files']) === 30;
    });

    Http::assertSent(function (Request $request) {
        return count($request['files']) === 15;
    });
});

it('throws an exception when cloudflare api returns an error', function () {
    Http::fake([
        'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache' => Http::response([
            'success' => false,
            'errors' => [
                ['code' => 1000, 'message' => 'Invalid purge request'],
            ],
            'result' => null,
        ], 400),
    ]);

    expect(fn () => app(PurgeCdnCache::class)->purgeVocabularyAudio([1]))
        ->toThrow(RuntimeException::class, 'Invalid purge request');
});
