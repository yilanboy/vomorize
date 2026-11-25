<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;

test('authenticated pages load without javascript errors', function (string $url) {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit($url)
        ->assertNoSmoke();
})->with([
    'dashboard' => '/dashboard',
    'profile settings' => '/settings/profile',
    'appearance settings' => '/settings/appearance',
]);

test('dashboard shows clean onboarding without zeroed kpis for new users', function () {
    $level = Level::factory()->withTranslation('zh_TW', '基礎入門')->create();
    Group::factory()->create([
        'level_id' => $level->id,
        'sequence' => 1,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/dashboard')
        ->assertNoSmoke()
        ->assertSee(__('ui.dashboard.title'))
        ->assertSee(__('ui.dashboard.onboarding.title'))
        ->assertSee(__('ui.dashboard.onboarding.start_cta'))
        ->assertDontSee(__('ui.dashboard.kpis.learning_words'))
        ->assertDontSee(__('ui.dashboard.kpis.mastered_words'));
});

test('dashboard shows core kpis, due reviews, and next recommendation for active users', function () {
    $level = Level::factory()->withTranslation('zh_TW', '基礎入門')->create();
    $groups = Group::factory()->count(3)->sequence(
        ['level_id' => $level->id, 'sequence' => 1],
        ['level_id' => $level->id, 'sequence' => 2],
        ['level_id' => $level->id, 'sequence' => 3],
    )->create();

    $user = User::factory()->create([
        'current_streak' => 4,
        'longest_streak' => 9,
        'last_studied_date' => now(),
    ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 2,
        'last_score' => 90,
        'next_review_at' => now()->subMinutes(15),
    ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[1]->id,
        'stage' => 6,
        'last_score' => 100,
        'next_review_at' => null,
    ]);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertNoSmoke()
        ->assertSee(__('ui.dashboard.kpis.streak'))
        ->assertSee(__('ui.dashboard.kpis.streak_checked_in'))
        ->assertSee(__('ui.dashboard.kpis.learning_words'))
        ->assertSee(__('ui.dashboard.kpis.mastered_words'))
        ->assertSee(__('ui.dashboard.action_center.due_title'))
        ->assertSee(__('ui.dashboard.action_center.start_quiz'))
        ->assertSee(__('ui.dashboard.action_center.recommended_new'))
        ->assertSee(__('ui.dashboard.action_center.start_learning'));
});

test('dashboard shows single cleared card when no reviews are due', function () {
    $level = Level::factory()->withTranslation('zh_TW', '基礎入門')->create();
    $groups = Group::factory()->count(2)->sequence(
        ['level_id' => $level->id, 'sequence' => 1],
        ['level_id' => $level->id, 'sequence' => 2],
    )->create();

    $user = User::factory()->create();

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 1,
        'last_score' => 90,
        'next_review_at' => now()->addHours(10),
    ]);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertNoSmoke()
        ->assertSee(__('ui.dashboard.action_center.due_cleared_title'))
        ->assertDontSee(__('ui.dashboard.action_center.recommended_new'));
});

test('security settings page loads with confirmed password without javascript errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()]);

    visit('/settings/security')
        ->assertNoSmoke();
});

test('confirm password page loads without javascript errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/user/confirm-password')
        ->assertNoSmoke()
        ->assertSee('安全確認')
        ->assertSee('密碼')
        ->assertSee('確認密碼');
});

test('profile settings page displays redesigned elements and navigation', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/settings/profile')
        ->assertNoSmoke()
        ->assertSee('帳號設定')
        ->assertSee('個人資料')
        ->assertSee('安全性設定')
        ->assertSee('外觀主題')
        ->assertSee('電子郵件地址')
        ->assertSee('刪除帳號');
});

test('appearance settings page displays redesigned elements and navigation', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/settings/appearance')
        ->assertNoSmoke()
        ->assertSee('外觀設定')
        ->assertSee('淺色模式')
        ->assertSee('深色模式')
        ->assertSee('跟隨系統');
});

it('can click back button', function () {
    app()->setLocale('zh_TW');

    visit(route('home'))
        ->click(__('ui.nav.login'))
        ->assertPathIs('/login')
        ->click(__('ui.auth.back'))
        ->assertPathIs('/')
        ->click(__('ui.nav.login'))
        ->click(__('ui.auth.login.sign_up'))
        ->assertPathIs('/register')
        ->click(__('ui.auth.back'))
        ->assertPathIs('/login');
});
