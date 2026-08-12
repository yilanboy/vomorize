<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/ConfirmPassword'),
    );
});

test('password confirmation requires authentication', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});

test('confirm password page provides localized translation strings for zh_TW, zh_CN, and ja', function () {
    $user = User::factory()->create();

    // Every locale ships on every response, so one request proves all three.
    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('translations.app.zh_TW.confirm_password_title', '確認密碼')
            ->where('translations.app.zh_TW.confirm_password_description', '這是應用程式的安全區域。請在繼續之前確認您的密碼。')
            ->where('translations.app.zh_TW.confirm_with_passkey', '使用通行金鑰確認')
            ->where('translations.app.zh_TW.or_confirm_with_password', '或使用密碼確認')
            ->where('translations.app.zh_TW.confirm_password_button', '確認密碼')
            ->where('translations.app.zh_TW.confirming', '確認中...')
            ->where('translations.app.zh_CN.confirm_password_title', '确认密码')
            ->where('translations.app.zh_CN.confirm_password_description', '这是应用程序的安全区域。请在继续之前确认您的密码。')
            ->where('translations.app.zh_CN.confirm_with_passkey', '使用通行密钥确认')
            ->where('translations.app.zh_CN.or_confirm_with_password', '或使用密码确认')
            ->where('translations.app.zh_CN.confirm_password_button', '确认密码')
            ->where('translations.app.zh_CN.confirming', '确认中...')
            ->where('translations.app.ja.confirm_password_title', 'パスワードの確認')
            ->where('translations.app.ja.confirm_password_description', 'これはアプリケーションの保護されたエリアです。続行する前にパスワードを確認してください。')
            ->where('translations.app.ja.confirm_with_passkey', 'パスキーで確認')
            ->where('translations.app.ja.or_confirm_with_password', 'またはパスワードで確認')
            ->where('translations.app.ja.confirm_password_button', 'パスワードを確定')
            ->where('translations.app.ja.confirming', '確認中...')
        );
});
