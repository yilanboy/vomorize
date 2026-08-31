<?php

use App\Enums\Locale;
use Illuminate\Support\Facades\Lang;

test('login page has no smoke', function (Locale $locale) {
    $page = visit(route('login', ['locale' => $locale->routeKey()]));
    $page->assertNoSmoke();
})->with(Locale::cases());

test('login page labels will switch by locale', function (Locale $locale) {
    visit(route('login', ['locale' => $locale->routeKey()]))
        ->assertSeeIn('@auth-title', Lang::get('app.login_title', locale: $locale->value))
        ->assertSeeIn('@auth-description', Lang::get('app.login_subtitle', locale: $locale->value))
        ->assertSeeIn('@email-label', Lang::get('app.email', locale: $locale->value))
        ->assertSeeIn('@password-label', Lang::get('app.password', locale: $locale->value))
        ->assertSeeIn('@forgot-password-link', Lang::get('app.forgot_password', locale: $locale->value))
        ->assertSeeIn('@remember-me-label', Lang::get('app.remember_me', locale: $locale->value))
        ->assertSeeIn('@login-button', Lang::get('app.login', locale: $locale->value))
        ->assertSeeIn('@or-divider', Lang::get('app.or', locale: $locale->value))
        ->assertSeeIn('@github-login-button', Lang::get('app.github_login', locale: $locale->value))
        ->assertSeeIn('@no-account-text', Lang::get('app.no_account', locale: $locale->value))
        ->assertSeeIn('@sign-up-link', Lang::get('app.sign_up', locale: $locale->value));
})->with(Locale::cases());
