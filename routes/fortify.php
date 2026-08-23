<?php

use App\Enums\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::prefix('{locale}')
    ->whereIn('locale', Locale::routeKeys())
    ->group(function () {
        Route::get('/login', fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]))
            ->name('login');

        Route::get('/register', fn () => Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]))
            ->name('register');

        Route::get('/forgot-password', fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]))
            ->name('password.request');

        Route::get('/reset-password/{token}', fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]))
            ->name('password.reset');

        Route::get('/two-factor-challenge', function (Request $request) {
            return ! $request->session()->has('login.id')
                ? redirect()->route('login')
                : Inertia::render('auth/TwoFactorChallenge');
        })
            ->name('two-factor.login');

        Route::get('/email/verify', function (Request $request) {
            return $request->user()?->hasVerifiedEmail()
                ? redirect()->route('home')
                : Inertia::render('auth/VerifyEmail', [
                    'status' => $request->session()->get('status'),
                ]);
        })
            ->name('verification.notice');

        Route::get('/user/confirm-password', fn () => Inertia::render('auth/ConfirmPassword'))
            ->middleware(['auth'])
            ->name('password.confirm');
    });
