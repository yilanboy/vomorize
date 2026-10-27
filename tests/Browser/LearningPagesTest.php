<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;

test('stage 1 intro page loads without javascript errors', function () {
    $level = Level::factory()->create(['name' => '基礎入門']);
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    Vocabulary::factory()
        ->count(10)
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

    visit("/groups/{$group->id}/intro")
        ->assertSee($group->sequence)
        ->assertNoSmoke();
});

test('stage 1 intro page guards against leaving and browser back from the very first step', function () {
    $level = Level::factory()->create(['name' => '基礎入門']);
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    Vocabulary::factory()
        ->count(10)
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

    $page = visit("/groups/{$group->id}/intro");

    $page
        ->assertSee('30')
        ->wait(2)
        ->click('button[title="離開"]')
        ->assertSee('確定要放棄本次學習？')
        ->click('繼續學習')
        ->assertDontSee('確定要放棄本次學習？');

    $page->script('window.history.back()');

    $page->assertSee('確定要放棄本次學習？')
        ->assertNoSmoke();
});

test('group show page for passed group loads without javascript errors', function () {
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

    visit("/groups/{$group->id}")
        ->assertNoSmoke();
});

test('stage 2 review quiz page loads without javascript errors and guards against leaving', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create(['name' => '基礎入門']);
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

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
        'last_score' => 85,
        'next_review_at' => now()->subMinute(),
    ]);

    $this->actingAs($user);

    $page = visit("/groups/{$group->id}/quiz");

    $page
        ->assertSee('20')
        ->wait(2)
        ->click('button[title="離開"]')
        ->assertSee('確定要放棄本次學習？')
        ->click('繼續學習')
        ->assertDontSee('確定要放棄本次學習？')
        ->assertNoSmoke();
});
