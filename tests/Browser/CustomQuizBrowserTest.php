<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;

test('guest visits custom quiz page with zero unlocked words and sees empty state', function () {
    visit('/quiz')
        ->assertNoSmoke()
        ->assertSee('自訂測驗')
        ->assertSee('尚未解鎖測驗題庫')
        ->assertSee('步驟 1')
        ->assertSee('步驟 2')
        ->assertSee('步驟 3')
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
        Vocabulary::factory()->count(10)->create([
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
        ->assertSee('需解鎖 30 字')
        ->assertSee('一般')
        ->assertSee('看例句選出正確的單字')
        ->assertSee('拼音')
        ->assertSee('即將推出')
        ->assertSee('開始自訂測驗 (20 題)');
});

test('authenticated member completing custom quiz displays result summary without error modals', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
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
    $page->assertNoSmoke()
        ->click('button:has-text("開始自訂測驗")')
        ->assertPathIs('/quiz/play')
        ->assertNoSmoke();

    for ($i = 1; $i <= 10; $i++) {
        $page->click('button:has-text("1")')->wait(1.2);
    }

    $page->assertSee('自訂測驗完成！')
        ->assertDontSee('All Inertia requests must receive a valid Inertia response')
        ->assertNoSmoke();
});
