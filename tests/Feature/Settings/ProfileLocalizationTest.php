<?php

use App\Models\User;

test('profile page provides localized translation strings for zh_TW, zh_CN, and ja', function () {
    $user = User::factory()->create(['locale' => 'zh_TW']);

    // Every locale ships on every response, so one request proves all three.
    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('translations.app.zh_TW.save_changes', '儲存變更')
            ->where('translations.app.zh_TW.saving', '儲存中...')
            ->where('translations.app.zh_TW.delete_account_warning_title', '警告：此操作不可復原')
            ->where('translations.app.zh_TW.delete_account_warning_desc', '一旦帳號被刪除，所有學習紀錄與相關設定都將被永久清除。請謹慎操作。')
            ->where('translations.app.zh_TW.confirm_delete_account_title', '確定要刪除您的帳號嗎？')
            ->where('translations.app.zh_TW.confirm_delete_account_desc', '帳號刪除後，您的所有學習進度與相關資料將會被永久移除。請輸入您的密碼以確認刪除。')
            ->where('translations.app.zh_TW.enter_password_confirm', '請輸入密碼以進行確認')
            ->where('translations.app.zh_TW.confirm_delete_account', '確認刪除帳號')
            ->where('translations.app.zh_TW.deleting', '刪除中...')
            ->where('translations.app.zh_CN.save_changes', '保存变更')
            ->where('translations.app.zh_CN.saving', '保存中...')
            ->where('translations.app.zh_CN.delete_account_warning_title', '警告：此操作不可撤销')
            ->where('translations.app.zh_CN.delete_account_warning_desc', '账号一旦被删除，所有学习记录与相关设置都将被永久清除。请谨慎操作。')
            ->where('translations.app.zh_CN.confirm_delete_account_title', '确定要删除您的账号吗？')
            ->where('translations.app.zh_CN.confirm_delete_account_desc', '账号删除后，您的所有学习进度与相关资料将会被永久移除。请输入您的密码以确认删除。')
            ->where('translations.app.zh_CN.enter_password_confirm', '请输入密码以进行确认')
            ->where('translations.app.zh_CN.confirm_delete_account', '确认删除账号')
            ->where('translations.app.zh_CN.deleting', '删除中...')
            ->where('translations.app.ja.save_changes', '変更を保存')
            ->where('translations.app.ja.saving', '保存中...')
            ->where('translations.app.ja.delete_account_warning_title', '警告：この操作は取り消せません')
            ->where('translations.app.ja.delete_account_warning_desc', 'アカウントが削除されると、すべての学習記録と関連設定が永久に消去されます。ご注意ください。')
            ->where('translations.app.ja.confirm_delete_account_title', '本当にアカウントを削除しますか？')
            ->where('translations.app.ja.confirm_delete_account_desc', 'アカウントが削除されると、すべての学習進捗と関連データが永久に削除されます。確認のためパスワードを入力してください。')
            ->where('translations.app.ja.enter_password_confirm', '確認のためパスワードを入力してください')
            ->where('translations.app.ja.confirm_delete_account', 'アカウント削除を確定')
            ->where('translations.app.ja.deleting', '削除中...')
        );
});
