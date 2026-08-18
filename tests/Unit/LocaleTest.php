<?php

use App\Enums\Locale;

it('will return the route keys', function () {
    expect(Locale::routeKeys())->toEqualCanonicalizing(['zh-tw', 'zh-cn', 'ja']);
});

it('will return labels with locale as key', function () {
    expect(Locale::getLabels())->toEqualCanonicalizing([
        Locale::ZH_TW->value => '繁體中文',
        Locale::ZH_CN->value => '简体中文',
        Locale::JP->value => '日本語',
    ]);
});

it('will return the locale from a route key or accept-language header', function () {
    expect(Locale::fromRouteKey('zh-tw'))->toBe(Locale::ZH_TW)
        ->and(Locale::fromRouteKey('zh-TW'))->toBe(Locale::ZH_TW)
        ->and(Locale::fromRouteKey('zh-cn'))->toBe(Locale::ZH_CN)
        ->and(Locale::fromRouteKey('zh-CN'))->toBe(Locale::ZH_CN)
        ->and(Locale::fromRouteKey('ja'))->toBe(Locale::JP)
        // ->and(Locale::fromRouteKey('ja-JP'))->toBe(Locale::JP)
        ->and(Locale::fromRouteKey(''))->toBeNull()
        ->and(Locale::fromRouteKey(null))->toBeNull()
        ->and(Locale::fromRouteKey('en'))->toBeNull();
});
