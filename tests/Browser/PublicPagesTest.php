<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\Level;
use App\Models\SentenceClozeQuestion;

test('public pages load without javascript errors', function (string $url) {
    SentenceClozeQuestion::factory()->create();

    visit($url)
        ->assertNoSmoke();
})->with([
    'home' => '/',
    'levels' => '/levels',
    'login' => '/login',
    'register' => '/register',
    'forgot password' => '/forgot-password',
]);

test('level detail page loads without javascript errors', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門')
        ->create();

    visit("/levels/{$level->id}")
        ->assertSee('基礎入門')
        ->assertNoSmoke();
});

test('header contains home and levels navigation links for guest and logo links to home', function () {
    visit('/levels')
        ->assertSee('首頁')
        ->assertSee('單字等級')
        ->assertSee('自訂測驗')
        ->assertNoSmoke();
});

test('clicking header logo navigates to home page', function () {
    SentenceClozeQuestion::factory()->create();

    visit('/levels')
        ->click('header span:text-is("Vomorize")')
        ->assertPathIs('/')
        ->assertNoSmoke();
});

test('clicking header home navigation link navigates to home page', function () {
    SentenceClozeQuestion::factory()->create();

    visit('/levels')
        ->click('首頁')
        ->assertPathIs('/')
        ->assertNoSmoke();
});

test('login page displays redesigned card elements and navigation', function () {
    visit('/login')
        ->assertNoSmoke()
        ->assertSee('歡迎回來')
        ->assertSee('電子郵件')
        ->assertSee('密碼')
        ->assertSee('記住我')
        ->assertSee('登入')
        ->assertSee('還沒有帳號？')
        ->assertSee('立即免費註冊')
        ->assertSee('返回');
});

test('register page displays redesigned card elements and navigation', function () {
    visit('/register')
        ->assertNoSmoke()
        ->assertSee('建立新帳號')
        ->assertSee('電子郵件')
        ->assertSee('密碼')
        ->assertSee('確認密碼')
        ->assertSee('免費註冊帳號')
        ->assertSee('已經有帳號了？')
        ->assertSee('直接登入')
        ->assertSee('返回');
});

test('landing page displays unified header, hero cta, and minimal footer', function () {
    SentenceClozeQuestion::factory()->create();

    visit('/')
        ->assertNoSmoke()
        ->assertSeeIn('@app-header', __('ui.nav.levels'))
        ->assertSeeIn('@app-header', __('ui.nav.login'))
        ->assertSeeIn('@app-footer', __('ui.footer.github'))
        ->click('a:has-text("'.__('ui.welcome.hero.start_learning').'")')
        ->assertPathIs('/levels')
        ->assertNoSmoke();
});

test('landing page hero workbench switches across real intro, cloze, and spelling cards', function () {
    $group = Group::factory()->withVocabularies(4)->create();
    SentenceClozeQuestion::factory()->for($group->vocabularies()->firstOrFail())->create();

    visit('/')
        ->assertNoSmoke()
        ->assertSeeIn('@hero-workbench-card', __('ui.intro.card.badge'))
        ->click('[data-test="hero-workbench"] [role="tab"]:has-text("'.__('ui.welcome.hero.modes.cloze').'")')
        ->assertVisible('[data-test="sentence-cloze-stem"]')
        ->click('[data-test="hero-workbench-card"] [data-test="correct-option"]')
        ->assertVisible('[data-test="sentence-cloze-reveal"]')
        ->click('[data-test="hero-workbench"] [role="tab"]:has-text("'.__('ui.welcome.hero.modes.spelling').'")')
        ->assertSeeIn('@hero-workbench-card', __('ui.quiz.spelling.badge'))
        ->assertNoSmoke();
});
