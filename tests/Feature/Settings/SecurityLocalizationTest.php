<?php

use App\Models\User;

test('security page provides localized translation strings for zh-tw, zh-cn, and ja', function () {
    $user = User::factory()->create();

    // Every locale ships on every response, so one request proves all three.
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('translations.app.zh-tw.current_password', '目前密碼')
            ->where('translations.app.zh-tw.new_password', '新密碼')
            ->where('translations.app.zh-tw.confirm_new_password', '確認新密碼')
            ->where('translations.app.zh-tw.save_password', '儲存密碼')
            ->where('translations.app.zh-tw.two_factor_auth', '雙重驗證 (2FA)')
            ->where('translations.app.zh-tw.enable_two_factor', '啟用雙重驗證')
            ->where('translations.app.zh-tw.disable_two_factor', '停用雙重驗證')
            ->where('translations.app.zh-tw.passkeys', 'Passkeys 快速登入')
            ->where('translations.app.zh-tw.add_passkey', '新增 Passkey')
            ->where('translations.app.zh-cn.current_password', '当前密码')
            ->where('translations.app.zh-cn.new_password', '新密码')
            ->where('translations.app.zh-cn.confirm_new_password', '确认新密码')
            ->where('translations.app.zh-cn.save_password', '保存密码')
            ->where('translations.app.zh-cn.two_factor_auth', '双重验证 (2FA)')
            ->where('translations.app.zh-cn.enable_two_factor', '启用双重验证')
            ->where('translations.app.zh-cn.disable_two_factor', '禁用双重验证')
            ->where('translations.app.zh-cn.passkeys', 'Passkeys 快速登录')
            ->where('translations.app.zh-cn.add_passkey', '新增 Passkey')
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
