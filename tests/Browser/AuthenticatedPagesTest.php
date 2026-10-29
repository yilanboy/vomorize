<?php

use App\Models\User;

test('authenticated pages load without javascript errors', function (string $url) {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit($url)
        ->assertNoSmoke();
})->with([
    'dashboard' => '/dashboard',
    'profile settings' => '/settings/profile',
    'appearance settings' => '/settings/appearance',
]);

test('security settings page loads with confirmed password without javascript errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()]);

    visit('/settings/security')
        ->assertNoSmoke();
});

test('confirm password page loads without javascript errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/user/confirm-password')
        ->assertNoSmoke()
        ->assertSee('安全確認')
        ->assertSee('密碼')
        ->assertSee('確認密碼');
});

test('settings layout and pages display redesigned elements and navigation', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/settings/profile')
        ->assertNoSmoke()
        ->assertSee('帳號設定')
        ->assertSee('個人資料')
        ->assertSee('安全性設定')
        ->assertSee('外觀主題')
        ->assertSee('電子郵件地址')
        ->assertSee('刪除帳號');

    visit('/settings/appearance')
        ->assertNoSmoke()
        ->assertSee('外觀設定')
        ->assertSee('淺色模式')
        ->assertSee('深色模式')
        ->assertSee('跟隨系統');
});
