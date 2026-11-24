<?php

declare(strict_types=1);

use App\Services\CloudflareCdnService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.cloudflare.key', 'test_key');
    Config::set('services.cloudflare.zone_id', 'test_zone_id');
});

it('throws an exception when cloudflare api key is missing', function () {
    Config::set('services.cloudflare.key', null);

    expect(fn () => app(CloudflareCdnService::class)->purge(['assets.vomorize.com/vocabulary/1/']))
        ->toThrow(RuntimeException::class, 'Cloudflare API key is not configured');
});

it('throws an exception when cloudflare zone id is missing', function () {
    Config::set('services.cloudflare.zone_id', null);

    expect(fn () => app(CloudflareCdnService::class)->purge(['assets.vomorize.com/vocabulary/1/']))
        ->toThrow(RuntimeException::class, 'Cloudflare Zone ID is not configured');
});

it('does nothing when prefixes array is empty', function () {
    Http::fake();

    app(CloudflareCdnService::class)->purge([]);

    Http::assertNothingSent();
});

it('purges deduplicated prefixes with correct payload and authorization header', function () {
    Http::fake([
        'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache' => Http::response([
            'success' => true,
            'errors' => [],
            'result' => ['id' => 'test_zone_id'],
        ], 200),
    ]);

    app(CloudflareCdnService::class)->purge([
        'assets.vomorize.com/vocabulary/1/',
        'assets.vomorize.com/vocabulary/2/',
        'assets.vomorize.com/vocabulary/1/',
    ]);

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache'
            && $request->hasHeader('Authorization', 'Bearer test_key')
            && $request['prefixes'] === [
                'assets.vomorize.com/vocabulary/1/',
                'assets.vomorize.com/vocabulary/2/',
            ];
    });
});

it('chunks prefixes into batches of 100 when purging', function () {
    Http::fake([
        'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache' => Http::response([
            'success' => true,
            'errors' => [],
            'result' => ['id' => 'test_zone_id'],
        ], 200),
    ]);

    $prefixes = array_map(fn (int $id): string => "assets.vomorize.com/vocabulary/{$id}/", range(1, 150));

    app(CloudflareCdnService::class)->purge($prefixes);

    Http::assertSentCount(2);

    Http::assertSent(fn (Request $request): bool => count($request['prefixes']) === 100);
    Http::assertSent(fn (Request $request): bool => count($request['prefixes']) === 50);
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

    expect(fn () => app(CloudflareCdnService::class)->purge(['assets.vomorize.com/vocabulary/1/']))
        ->toThrow(RuntimeException::class, 'Invalid purge request');
});
