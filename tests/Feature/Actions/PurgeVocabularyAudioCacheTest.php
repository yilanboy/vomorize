<?php

declare(strict_types=1);

use App\Actions\PurgeVocabularyAudioCache;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.cloudflare.key', 'test_key');
    Config::set('services.cloudflare.zone_id', 'test_zone_id');
    Config::set('filesystems.disks.r2.url', 'https://assets.vomorize.com');
});

it('does nothing when vocabulary ids array is empty', function () {
    Http::fake();

    app(PurgeVocabularyAudioCache::class)->handle([]);

    Http::assertNothingSent();
});

it('purges vocabulary audio prefixes built from r2 host', function () {
    Http::fake([
        'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache' => Http::response([
            'success' => true,
            'errors' => [],
            'result' => ['id' => 'test_zone_id'],
        ], 200),
    ]);

    app(PurgeVocabularyAudioCache::class)->handle([1, 2]);

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache'
            && $request['prefixes'] === [
                'assets.vomorize.com/vocabulary/1/',
                'assets.vomorize.com/vocabulary/2/',
            ];
    });
});

it('handles r2 url without scheme when building vocabulary audio prefixes', function () {
    Config::set('filesystems.disks.r2.url', 'cdn.vomorize.com/');

    Http::fake([
        'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache' => Http::response([
            'success' => true,
            'errors' => [],
            'result' => ['id' => 'test_zone_id'],
        ], 200),
    ]);

    app(PurgeVocabularyAudioCache::class)->handle([42]);

    Http::assertSent(function (Request $request) {
        return $request['prefixes'] === ['cdn.vomorize.com/vocabulary/42/'];
    });
});
