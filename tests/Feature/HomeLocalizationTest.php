<?php

use function Pest\Laravel\get;

/**
 * The client decides which locale to display, so one response carries them all.
 *
 * This replaces a walkthrough that switched locale between requests and re-read the resolved
 * copy each time. With every locale on every response that sequence no longer proves anything
 * about copy — only the resolved locale value still moves, which the second test covers.
 */
it('carries interface copy for every locale in one response', function () {
    get('/')->assertInertia(fn ($page) => $page
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
    get('/')->assertInertia(fn ($page) => $page
        ->has('translations.app', 3)
        ->has('translations.app.zh_TW')
        ->has('translations.app.zh_CN')
        ->has('translations.app.ja')
    );
});

it('uses a Japanese browser language for a first-time visitor', function () {
    get('/', ['HTTP_ACCEPT_LANGUAGE' => 'ja-JP,ja;q=0.9'])
        ->assertInertia(fn ($page) => $page->where('locale', 'ja'));
});
