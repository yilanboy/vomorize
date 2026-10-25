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
