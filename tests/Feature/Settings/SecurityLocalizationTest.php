<?php

use App\Models\User;

test('security page provides localized translation strings for zh_TW, zh_CN, and ja', function () {
    $user = User::factory()->create(['locale' => 'zh_TW']);

    // Every locale ships on every response, so one request proves all three.
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('translations.app.zh_TW.current_password', '目前密碼')
            ->where('translations.app.zh_TW.new_password', '新密碼')
            ->where('translations.app.zh_TW.confirm_new_password', '確認新密碼')
            ->where('translations.app.zh_TW.save_password', '儲存密碼')
            ->where('translations.app.zh_TW.two_factor_auth', '雙重驗證 (2FA)')
            ->where('translations.app.zh_TW.enable_two_factor', '啟用雙重驗證')
            ->where('translations.app.zh_TW.disable_two_factor', '停用雙重驗證')
            ->where('translations.app.zh_TW.passkeys', 'Passkeys 快速登入')
            ->where('translations.app.zh_TW.add_passkey', '新增 Passkey')
            ->where('translations.app.zh_CN.current_password', '当前密码')
            ->where('translations.app.zh_CN.new_password', '新密码')
            ->where('translations.app.zh_CN.confirm_new_password', '确认新密码')
            ->where('translations.app.zh_CN.save_password', '保存密码')
            ->where('translations.app.zh_CN.two_factor_auth', '双重验证 (2FA)')
            ->where('translations.app.zh_CN.enable_two_factor', '启用双重验证')
            ->where('translations.app.zh_CN.disable_two_factor', '禁用双重验证')
            ->where('translations.app.zh_CN.passkeys', 'Passkeys 快速登录')
            ->where('translations.app.zh_CN.add_passkey', '新增 Passkey')
            ->where('translations.app.ja.current_password', '現在のパスワード')
            ->where('translations.app.ja.new_password', '新しいパスワード')
            ->where('translations.app.ja.confirm_new_password', '新しいパスワードの確認')
            ->where('translations.app.ja.save_password', 'パスワードを保存')
            ->where('translations.app.ja.two_factor_auth', '2要素認証 (2FA)')
            ->where('translations.app.ja.enable_two_factor', '2段階認証を有効化')
            ->where('translations.app.ja.disable_two_factor', '2段階認証を無効化')
            ->where('translations.app.ja.passkeys', 'Passkey ログイン')
            ->where('translations.app.ja.add_passkey', 'Passkeyを追加')
        );
});
