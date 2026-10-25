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
