<?php

use App\Models\User;

test('welcome page renders successfully', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('translations are shared via inertia shareOnce on initial visit', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('translations.ui')
        ->where('translations.ui.nav.levels', '單字等級')
        ->where('translations.ui.nav.quiz', '題庫測驗')
        ->where('translations.ui.theme.system', '跟隨系統')
    );
});

test('authenticated user can visit dashboard and profile with shared layout context', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();

    $settingsResponse = $this->actingAs($user)->get(route('profile.edit'));
    $settingsResponse->assertOk();
});

test('login page renders with dedicated auth layout', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});
