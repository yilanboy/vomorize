<?php

use App\Enums\Locale;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function (Locale $locale) {
    $response = $this->get(route('register', [
        'locale' => $locale->routeKey(),
    ]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/Register')
            ->has("translations.app.$locale->value.register_title")
            ->has("translations.app.$locale->value.create_account")
        );
})->with(Locale::cases());

test('new users can register', function (Locale $locale) {
    Notification::fake();

    app()->setLocale($locale->value);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route(
        name: 'verification.notice',
        parameters: ['locale' => $locale->routeKey()],
        absolute: false)
    );

    $user = User::where('email', 'test@example.com')->first();
    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
})->with(Locale::cases());
