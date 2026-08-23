<?php

use App\Enums\Locale;

use function Pest\Laravel\get;
use function Pest\Laravel\withUnencryptedCookie;

it('carries interface copy for every locale in one response', function (string $routeKey) {
    get("/$routeKey")->assertInertia(fn ($page) => $page
        ->where('translations.app.zh_TW.home_title', '英語單字核心記憶')
        ->where('translations.app.zh_TW.home_subtitle', '7,000 個高頻詞彙 · 間隔重複記憶法')
        ->where('translations.app.zh_TW.level_stats', '1,000 單字 · 100 組')
        ->where('translations.app.zh_CN.home_title', '英语单字核心记忆')
        ->where('translations.app.zh_CN.home_subtitle', '7,000 个高频词汇 · 间隔重复记忆法')
        ->where('translations.app.zh_CN.level_stats', '1,000 单词 · 100 组')
        ->where('translations.app.ja.home_title', '英語単語核心記憶')
        ->where('translations.app.ja.home_subtitle', '7,000語の高頻度語彙・間隔反復記憶法')
        ->where('translations.app.ja.level_stats', '1,000単語・100グループ')
    );
})->with(Locale::routeKeys());

it('carries every supported locale, and nothing that is not supported', function (string $routeKey) {
    get("/$routeKey")->assertInertia(fn ($page) => $page
        ->has('translations.app', 3)
        ->has('translations.app.zh_TW')
        ->has('translations.app.zh_CN')
        ->has('translations.app.ja')
    );
})->with(Locale::routeKeys());

it('redirects root to default zh-tw when no header or cookie is present', function () {
    get('/')
        ->assertStatus(302)
        ->assertRedirect('/zh-tw');
});

it('redirects root to correct locale when cookie is present', function (string $routeKey) {
    withUnencryptedCookie('locale', Locale::fromRouteKey($routeKey)->value)
        ->get('/')
        ->assertStatus(302)
        ->assertRedirect('/'.$routeKey);
})->with(Locale::routeKeys());

it('queues cookie and sets locale when visiting localized routes', function (string $routeKey) {
    $response = get("/$routeKey");

    $response->assertOk()
        ->assertPlainCookie('locale', Locale::fromRouteKey($routeKey)->value)
        ->assertInertia(fn ($page) => $page
            ->where('locale', Locale::fromRouteKey($routeKey)->value)
        );

    expect(app()->getLocale())->toBe(Locale::fromRouteKey($routeKey)->value);
})->with(Locale::routeKeys());
