<?php

use App\Enums\Locale;
use Illuminate\Support\Facades\Lang;

test('register page has no smoke', function (Locale $locale) {
    $page = visit(route('register', ['locale' => $locale->routeKey()]));
    $page->assertNoSmoke();
})->with(Locale::cases());

test('register page labels will switch by locale', function (Locale $locale) {
    visit(route('register', ['locale' => $locale->routeKey()]))
        ->assertSeeIn('@auth-title', Lang::get('app.register_title', locale: $locale->value))
        ->assertSeeIn('@auth-description', Lang::get('app.register_subtitle', locale: $locale->value))
        ->assertSeeIn('@name-label', Lang::get('app.name', locale: $locale->value))
        ->assertSeeIn('@email-label', Lang::get('app.email', locale: $locale->value))
        ->assertSeeIn('@password-label', Lang::get('app.password', locale: $locale->value))
        ->assertSeeIn('@confirm-password-label', Lang::get('app.confirm_password', locale: $locale->value))
        ->assertSeeIn('@register-user-button', Lang::get('app.create_account', locale: $locale->value))
        ->assertSeeIn('@or-divider', Lang::get('app.or', locale: $locale->value))
        ->assertSeeIn('@github-register-button', Lang::get('app.github_register', locale: $locale->value))
        ->assertSeeIn('@already-have-account-text', Lang::get('app.already_have_account', locale: $locale->value))
        ->assertSeeIn('@login-link', Lang::get('app.login', locale: $locale->value));
})->with(Locale::cases());
