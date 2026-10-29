<?php

use App\Models\Level;

test('public pages load without javascript errors', function (string $url) {
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
    $level = Level::factory()->create([
        'name' => '基礎入門',
    ]);

    visit("/levels/{$level->id}")
        ->assertSee('基礎入門')
        ->assertNoSmoke();
});

test('header contains home and levels navigation links for guest and logo links to home', function () {
    visit('/levels')
        ->assertSee('首頁')
        ->assertSee('單字等級')
        ->assertNoSmoke();
});

test('clicking header logo navigates to home page', function () {
    visit('/levels')
        ->click('header span:text-is("Vomorize")')
        ->assertPathIs('/')
        ->assertNoSmoke();
});

test('clicking header home navigation link navigates to home page', function () {
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
        ->assertSee('返回首頁');
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
        ->assertSee('返回首頁');
});
