<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;

test('user can switch language via header switcher without page reload and see localized content', function () {
    $user = User::factory()->create();

    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '核心常用 1,000 單字')
        ->withTranslation('ja', '基礎入門 (日本語)', '日常でよく使う基本の 1,000 単語')
        ->withTranslation('zh_CN', '基础入门', '核心常用 1,000 单词')
        ->create();

    $group = Group::factory()->create([
        'level_id' => $level->id,
        'sequence' => 1,
    ]);

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

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
    ]);

    $this->actingAs($user);

    $page = visit("/groups/{$group->id}");

    $page->assertSee('單字等級')
        ->assertSee('基礎入門')
        ->assertSee('綿羊')
        ->assertSee('綿羊正在田野裡吃草。')
        ->assertNoSmoke();

    $zhTwFont = (string) $page->script('() => getComputedStyle(document.body).fontFamily');
    expect($zhTwFont)->toContain('Noto Sans TC');
    expect(strpos($zhTwFont, 'Noto Sans TC'))->toBeLessThan(strpos($zhTwFont, 'Noto Sans SC'));
    expect(strpos($zhTwFont, 'Noto Sans TC'))->toBeLessThan(strpos($zhTwFont, 'Noto Sans JP'));

    // Switch to Japanese via header dropdown
    $page->click('[aria-label="切換站點語系"]')
        ->click('日本語')
        ->assertSee('単語レベル')
        ->assertSee('基礎入門 (日本語)')
        ->assertSee('羊')
        ->assertSee('羊が野原で草を食べています。')
        ->assertNoSmoke();

    $jaFont = (string) $page->script('() => getComputedStyle(document.body).fontFamily');
    expect($page->script('() => document.documentElement.lang'))->toBe('ja');
    expect($jaFont)->toContain('Noto Sans JP');
    expect(strpos($jaFont, 'Noto Sans JP'))->toBeLessThan(strpos($jaFont, 'Noto Sans TC'));
    expect(strpos($jaFont, 'Noto Sans JP'))->toBeLessThan(strpos($jaFont, 'Noto Sans SC'));

    // Switch to Simplified Chinese via header dropdown
    $page->click('[aria-label="言語を切り替え"]')
        ->click('简体中文')
        ->assertSee('单词等级')
        ->assertSee('基础入门')
        ->assertSee('绵羊')
        ->assertSee('绵羊正在田野里吃草。')
        ->assertNoSmoke();

    $zhCnFont = (string) $page->script('() => getComputedStyle(document.body).fontFamily');
    expect($page->script('() => document.documentElement.lang'))->toBe('zh-CN');
    expect($zhCnFont)->toContain('Noto Sans SC');
    expect(strpos($zhCnFont, 'Noto Sans SC'))->toBeLessThan(strpos($zhCnFont, 'Noto Sans TC'));
    expect(strpos($zhCnFont, 'Noto Sans SC'))->toBeLessThan(strpos($zhCnFont, 'Noto Sans JP'));
});

test('user can switch language on auth layout pages and card title updates reactively', function () {
    $page = visit('/login');

    $page->assertSee('歡迎回來')
        ->assertSee('電子郵件')
        ->assertNoSmoke();

    $page->click('[aria-label="切換站點語系"]')
        ->click('日本語')
        ->assertSee('おかえりなさい')
        ->assertSee('メールアドレス')
        ->assertNoSmoke();

    $page->click('[aria-label="言語を切り替え"]')
        ->click('简体中文')
        ->assertSee('欢迎回来')
        ->assertSee('电子邮箱')
        ->assertNoSmoke();
});
