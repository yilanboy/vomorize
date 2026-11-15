<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;

test('guest visits custom quiz page with zero unlocked words and sees empty state', function () {
    visit('/quiz')
        ->assertNoSmoke()
        ->assertSee('自訂測驗')
        ->assertSee('尚未解鎖自訂測驗')
        ->assertSee('前往單字等級開始學習');
});

test('clicking empty state action navigates to levels page', function () {
    visit('/quiz')
        ->assertNoSmoke()
        ->click('前往單字等級開始學習')
        ->assertPathIs('/levels')
        ->assertNoSmoke();
});

test('clicking custom quiz link in header navigates to custom quiz page', function () {
    visit('/levels')
        ->assertNoSmoke()
        ->click('header a:text-is("自訂測驗")')
        ->assertPathIs('/quiz')
        ->assertNoSmoke();
});

test('authenticated member with unlocked words sees word pool count and question options', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $groups = Group::factory()->count(2)->create(['level_id' => $level->id]);

    foreach ($groups as $group) {
        Vocabulary::factory()
            ->count(10)
            ->create([
                'level_id' => $level->id,
                'group_id' => $group->id,
            ]);

        LearningProgress::factory()->create([
            'user_id' => $user->id,
            'level_id' => $level->id,
            'group_id' => $group->id,
            'stage' => 1,
        ]);
    }

    $this->actingAs($user);

    visit('/quiz')
        ->assertNoSmoke()
        ->assertSee('自訂測驗')
        ->assertSee('已解鎖題庫')
        ->assertSee('20 個單字')
        ->assertSee('選擇測驗題數')
        ->assertSee('10 題')
        ->assertSee('20 題')
        ->assertSee('30 題')
        ->assertSee('還差 10 題解鎖')
        ->assertSee('一般')
        ->assertSee('看例句選出正確的單字')
        ->assertSee('拼音')
        ->assertSee('單字拼寫')
        ->assertSee('即將推出')
        ->assertSee('開始自訂測驗 (10 題)');
});

test('authenticated member can select spelling mode and enter custom quiz room', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()
        ->count(10)
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
    ]);

    $this->actingAs($user);

    $page = visit('/quiz');
    $page
        ->assertNoSmoke()
        ->assertSee('單字拼寫')
        ->click('button:has-text("拼音")')
        ->assertSee('已配置：拼音模式')
        ->click('button:has-text("開始自訂測驗")')
        ->wait(2)
        ->assertNoSmoke()
        ->assertPathIs('/quiz/play')
        ->assertSee('拼音測驗')
        ->assertSee('退格')
        ->assertSee('清除');
});

test('authenticated member completing custom quiz displays result summary without error modals', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()
        ->count(10)
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
    ]);

    $this->actingAs($user);

    $page = visit('/quiz');
    $page
        ->assertNoSmoke()
        ->assertSee('開始自訂測驗 (10 題)')
        ->click('button:has-text("開始自訂測驗")')
        ->wait(2)
        ->assertNoSmoke()
        ->assertPathIs('/quiz/play');

    for ($i = 1; $i <= 10; $i++) {
        $page->click('button:has-text("1")')->wait(1.2);
    }

    $page
        ->assertSee('自訂測驗完成！')
        ->assertDontSee('All Inertia requests must receive a valid Inertia response')
        ->assertNoSmoke();
});

it("can remember user's choices", function () {
    app()->setLocale('zh_TW');

    $user = loginAsUser();

    $level = Level::factory()->create();
    $groups = Group::factory()
        ->count(2)
        ->create([
            'level_id' => $level->id,
        ]);
    $user->learningProgress()->createMany([
        [
            'level_id' => $level->id,
            'group_id' => $groups[0]->id,
            'stage' => 1,
            'last_score' => 90,
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subDay(),
        ],
        [
            'level_id' => $level->id,
            'group_id' => $groups[1]->id,
            'stage' => 1,
            'last_score' => 90,
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subDay(),
        ],
    ]);

    $page = visit('/quiz');
    $page
        ->assertNoSmoke()
        ->assertDontSee('尚未解鎖自訂測驗')
        ->assertSee('選擇測驗題數')
        ->assertSee('測驗題型模式')
        ->assertAriaAttribute('@count-option-10', 'pressed', 'true')
        ->assertAriaAttribute('@count-option-20', 'pressed', 'false')
        ->assertAriaAttribute('@mode-option-standard', 'pressed', 'true')
        ->assertAriaAttribute('@mode-option-spelling', 'pressed', 'false')
        ->assertSee('已配置：一般模式 · 10 題')
        ->click('@count-option-20')
        ->click('@mode-option-spelling')
        ->assertAriaAttribute('@count-option-20', 'pressed', 'true')
        ->assertAriaAttribute('@mode-option-spelling', 'pressed', 'true')
        ->assertSee('已配置：拼音模式 · 20 題')
        ->click('a:has-text("單字等級")')
        ->assertPathIs('/levels')
        ->assertNoSmoke()
        ->click('a:has-text("自訂測驗")')
        ->assertPathIs('/quiz')
        ->assertNoSmoke()
        ->assertAriaAttribute('@count-option-20', 'pressed', 'true')
        ->assertAriaAttribute('@mode-option-spelling', 'pressed', 'true')
        ->assertSee('已配置：拼音模式 · 20 題');
});

it('falls back to 10 questions when switching to a user with only 10 unlocked words', function () {
    app()->setLocale('zh_TW');

    $level = Level::factory()->create();
    $groups = Group::factory()
        ->count(2)
        ->create([
            'level_id' => $level->id,
        ]);

    $userWith20Words = User::factory()->create([
        'name' => 'Twenty User',
    ]);
    $userWith20Words->learningProgress()->createMany([
        [
            'level_id' => $level->id,
            'group_id' => $groups[0]->id,
            'stage' => 1,
            'last_score' => 90,
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subDay(),
        ],
        [
            'level_id' => $level->id,
            'group_id' => $groups[1]->id,
            'stage' => 1,
            'last_score' => 90,
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subDay(),
        ],
    ]);

    $userWith10Words = User::factory()->create([
        'name' => 'Ten User',
    ]);
    $userWith10Words->learningProgress()->create([
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 1,
        'last_score' => 90,
        'last_reviewed_at' => now()->subDay(),
        'next_review_at' => now()->subDay(),
    ]);

    loginAsUser($userWith20Words);

    $page = visit('/quiz');
    $page
        ->assertNoSmoke()
        ->assertSee('20 個單字')
        ->click('@count-option-20')
        ->assertAriaAttribute('@count-option-20', 'pressed', 'true')
        ->assertSee('已配置：一般模式 · 20 題')
        ->click('header button:has-text("TU")')
        ->click('@logout-button')
        ->wait(1)
        ->click('header a:has-text("登入")')
        ->assertPathIs('/login')
        ->fill('#email', $userWith10Words->email)
        ->fill('#password', 'password')
        ->click('@login-button')
        ->wait(1)
        ->click('header a:has-text("自訂測驗")')
        ->assertPathIs('/quiz')
        ->assertNoSmoke()
        ->assertSee('10 個單字')
        ->assertAriaAttribute('@count-option-10', 'pressed', 'true')
        ->assertAriaAttribute('@count-option-20', 'pressed', 'false')
        ->assertSee('已配置：一般模式 · 10 題');
});

it('falls back to backend defaults when stored preferences are outside allowed ranges', function () {
    app()->setLocale('zh_TW');

    $user = loginAsUser();
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);
    $user->learningProgress()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 90,
        'last_reviewed_at' => now()->subDay(),
        'next_review_at' => now()->subDay(),
    ]);

    $page = visit('/quiz');
    $page->script(<<<'JS'
        localStorage.setItem('quiz_question_count', '999');
        localStorage.setItem('quiz_mode', 'sentence_cloze');
    JS);

    $page
        ->navigate('/quiz')
        ->assertNoSmoke()
        ->assertAriaAttribute('@count-option-10', 'pressed', 'true')
        ->assertAriaAttribute('@mode-option-standard', 'pressed', 'true')
        ->assertAriaAttribute('@mode-option-spelling', 'pressed', 'false')
        ->assertSee('已配置：一般模式 · 10 題');
});
