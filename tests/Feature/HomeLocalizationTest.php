<?php

use function Pest\Laravel\get;
use function Pest\Laravel\withUnencryptedCookie;

it('carries interface copy for every locale in one response', function () {
    get('/zh-tw')->assertInertia(fn ($page) => $page
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
});

it('carries every supported locale, and nothing that is not supported', function () {
    get('/zh-tw')->assertInertia(fn ($page) => $page
        ->has('translations.app', 3)
        ->has('translations.app.zh_TW')
        ->has('translations.app.zh_CN')
        ->has('translations.app.ja')
    );
});

it('redirects root to default zh-tw when no header or cookie is present', function () {
    get('/')
        ->assertStatus(302)
        ->assertRedirect('/zh-tw');
});

it('redirects root to Japanese for a first-time Japanese visitor based on Accept-Language', function () {
    get('/', ['Accept-Language' => 'ja-JP,ja;q=0.9'])
        ->assertStatus(302)
        ->assertRedirect('/ja');
});

it('redirects root to Simplified Chinese for a first-time Chinese visitor based on Accept-Language', function () {
    get('/', ['Accept-Language' => 'zh-CN,zh;q=0.9'])
        ->assertStatus(302)
        ->assertRedirect('/zh-cn');
});

it('prioritizes cookie over Accept-Language header on root visits', function () {
    withUnencryptedCookie('locale', 'zh-cn')
        ->get('/', ['Accept-Language' => 'ja-JP,ja;q=0.9'])
        ->assertStatus(302)
        ->assertRedirect('/zh-cn');
});

it('queues cookie and sets locale when visiting localized routes', function () {
    $response = get('/ja');

    $response->assertOk()
        ->assertPlainCookie('locale', 'ja')
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'ja')
            ->where('locale_route_key', 'ja')
        );

    expect(app()->getLocale())->toBe('ja');
});
