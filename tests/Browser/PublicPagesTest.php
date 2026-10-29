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
