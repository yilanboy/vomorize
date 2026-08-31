<?php

use App\Enums\Locale;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('confirm password screen can be rendered', function (Locale $locale) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('password.confirm', ['locale' => $locale->routeKey()]));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/ConfirmPassword'),
    );
})->with(Locale::cases());

test('password confirmation requires authentication', function (Locale $locale) {
    $response = $this->get(route('password.confirm', ['locale' => $locale->routeKey()]));

    $response->assertRedirect(
        route('login', ['locale' => $locale->routeKey()])
    );
})->with(Locale::cases());
