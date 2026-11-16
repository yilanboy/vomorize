<?php

declare(strict_types=1);

use App\Enums\Locale;
use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use Database\Seeders\LevelSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('defaults to zh_TW when no locale cookie or matching Accept-Language header is present', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '核心常用 1,000 單字')
        ->withTranslation('ja', '基礎入門 (JA)', '日常でよく使う基本の 1,000 単語')
        ->create();

    $response = $this->withHeaders([
        'Accept-Language' => 'en-US,en;q=0.9',
    ])->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Page')
        ->where('locale', 'zh_TW')
        ->where('translations.ui.nav.levels', '單字等級')
        ->where('levels.0.id', $level->id)
        ->where('levels.0.name', '基礎入門')
        ->where('levels.0.description', '核心常用 1,000 單字')
    );
});

test('detects initial locale from Accept-Language header when cookie is absent', function (string $header, string $expectedLocale, string $expectedNavLevels) {
    Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '繁中描述')
        ->withTranslation('ja', '基礎入門JA', '日本語説明')
        ->withTranslation('zh_CN', '基础入门', '简中描述')
        ->create();

    $response = $this->withHeaders([
        'Accept-Language' => $header,
    ])->get(route('levels.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('locale', $expectedLocale)
        ->where('translations.ui.nav.levels', $expectedNavLevels)
    );
})->with([
    'japanese' => ['ja,en-US;q=0.9', 'ja', '単語レベル'],
    'japanese region' => ['ja-JP,ja;q=0.9', 'ja', '単語レベル'],
    'simplified chinese region' => ['zh-CN,zh;q=0.9', 'zh_CN', '单词等级'],
    'simplified chinese script' => ['zh-Hans,en;q=0.8', 'zh_CN', '单词等级'],
    'traditional chinese' => ['zh-TW,zh;q=0.9', 'zh_TW', '單字等級'],
    'quality weighted preference' => ['en-US;q=0.8,ja;q=0.9', 'ja', '単語レベル'],
]);

test('locale cookie overrides Accept-Language and loads localized level and vocabulary translations', function (string $locale, string $expectedLevelName, string $expectedDefinition, string $expectedExampleTranslation, string $expectedNavLevels) {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '核心常用 1,000 單字')
        ->withTranslation('ja', '基礎入門 (日本語)', '日常でよく使う基本の 1,000 単語')
        ->withTranslation('zh_CN', '基础入门', '核心常用 1,000 单词')
        ->create();

    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    Vocabulary::factory()
        ->withTranslation('zh_TW', '綿羊', '綿羊正在田野裡吃草。')
        ->withTranslation('ja', '羊', '羊が野原で草を食べています。')
        ->withTranslation('zh_CN', '绵羊', '绵羊正在田野里吃草。')
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
            'word' => 'sheep',
            'part_of_speech' => 'n.',
            'pronunciation' => '/ˈʃip/',
            'example_sentence' => 'The sheep are eating grass in the field.',
        ]);

    $response = $this->withUnencryptedCookie('locale', $locale)
        ->withHeaders(['Accept-Language' => 'en-US'])
        ->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('groups/show/Page')
        ->where('locale', $locale)
        ->where('translations.ui.nav.levels', $expectedNavLevels)
        ->where('group.level_name', $expectedLevelName)
        ->where('vocabularies.0.word', 'sheep')
        ->where('vocabularies.0.definition', $expectedDefinition)
        ->where('vocabularies.0.example_translation', $expectedExampleTranslation)
    );
})->with([
    'ja' => ['ja', '基礎入門 (日本語)', '羊', '羊が野原で草を食べています。', '単語レベル'],
    'zh_CN' => ['zh_CN', '基础入门', '绵羊', '绵羊正在田野里吃草。', '单词等级'],
    'zh_TW' => ['zh_TW', '基礎入門', '綿羊', '綿羊正在田野裡吃草。', '單字等級'],
]);

test('invalid locale cookie falls back to Accept-Language or zh_TW', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '繁中描述')
        ->create();

    $response = $this->withUnencryptedCookie('locale', 'fr_FR')
        ->withHeaders(['Accept-Language' => 'en-US'])
        ->get(route('levels.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('locale', 'zh_TW')
        ->where('levels.0.id', $level->id)
        ->where('levels.0.name', '基礎入門')
    );
});

test('missing translation for active locale gracefully falls back to zh_TW', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '核心常用 1,000 單字')
        ->create();

    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    Vocabulary::factory()
        ->withTranslation('zh_TW', '綿羊', '綿羊正在田野裡吃草。')
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
            'word' => 'sheep',
        ]);

    $response = $this->withUnencryptedCookie('locale', 'ja')
        ->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('locale', 'ja')
        ->where('group.level_name', '基礎入門')
        ->where('vocabularies.0.definition', '綿羊')
        ->where('vocabularies.0.example_translation', '綿羊正在田野裡吃草。')
    );
});

test('inertia onceProps cache keys rotate per locale for SPA reload without full refresh', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '繁中描述')
        ->withTranslation('ja', '基礎入門 (日本語)', '日本語説明')
        ->create();

    // First visit with zh_TW
    $initial = $this->withUnencryptedCookie('locale', 'zh_TW')
        ->get(route('levels.index'));

    $initial->assertOk();
    $initial->assertInertia(fn (Assert $page) => $page
        ->where('locale', 'zh_TW')
        ->where('translations.ui.nav.levels', '單字等級')
    );

    $version = $initial->viewData('page')['version'] ?? '';

    // Simulate SPA reload after switching cookie to ja while client holds zh_TW onceProps
    $switched = $this->withUnencryptedCookie('locale', 'ja')
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) $version,
            'X-Inertia-Except-Once-Props' => 'locale:zh_TW,translations:zh_TW',
        ])
        ->get(route('levels.index'));

    $switched->assertOk();
    $payload = $switched->json();

    expect($payload['props']['locale'])->toBe('ja');
    expect($payload['props']['translations']['ui']['nav']['levels'])->toBe('単語レベル');
    expect($payload['props']['levels'][0]['name'])->toBe('基礎入門 (日本語)');
    expect($payload['onceProps'])->toHaveKeys(['locale:ja', 'translations:ja']);

    // Subsequent SPA request in ja with ja onceProps cached skips resending translations
    $cached = $this->withUnencryptedCookie('locale', 'ja')
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) $version,
            'X-Inertia-Except-Once-Props' => 'locale:ja,translations:ja',
        ])
        ->get(route('levels.index'));

    $cached->assertOk();
    $cachedPayload = $cached->json();

    expect($cachedPayload['props'])->not->toHaveKey('translations');
    expect($cachedPayload['props'])->not->toHaveKey('locale');
});

test('level seeder populates translations for all supported locales', function () {
    $this->seed(LevelSeeder::class);

    expect(Level::count())->toBe(7);

    foreach (Locale::values() as $locale) {
        app()->setLocale($locale);
        $level = Level::query()->with('translations')->findOrFail(1);

        expect($level->translations)->toHaveCount(count(Locale::values()));
        expect($level->name)->not->toBe('');
        expect($level->description)->not->toBe('');
    }
});
