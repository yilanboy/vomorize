<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;

test('stage 1 intro page loads without javascript errors', function () {
    $level = Level::factory()->create(['name' => __('ui.dashboard.levels.subtitles.1')]);
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
    $level = Level::factory()->create(['name' => __('ui.dashboard.levels.subtitles.1')]);
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
        ->click('button[title="'.__('ui.intro.top_bar.leave').'"]')
        ->assertSee(__('ui.intro.top_bar.leave_dialog_title'))
        ->click(__('ui.intro.top_bar.leave_dialog_cancel'))
        ->assertDontSee(__('ui.intro.top_bar.leave_dialog_title'));

    $page->script('window.history.back()');

    $page->assertSee(__('ui.intro.top_bar.leave_dialog_title'))
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
    $level = Level::factory()->create(['name' => __('ui.dashboard.levels.subtitles.1')]);
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
        ->click('button[title="'.__('ui.intro.top_bar.leave').'"]')
        ->assertSee(__('ui.intro.top_bar.leave_dialog_title'))
        ->click(__('ui.intro.top_bar.leave_dialog_cancel'))
        ->assertDontSee(__('ui.intro.top_bar.leave_dialog_title'))
        ->assertNoSmoke();
});

test('user completes stage 1 intro and quiz with perfect score using correct-option', function () {
    //
})->skip('Practice: finish intro cards, answer all 20 quiz questions via @correct-option, and assert 100 / 100 passed result');

test('stage 1 quiz result score matches partial correct answers and shows failed state below 80', function () {
    //
})->skip('Practice: answer wrong options to score below 80 and assert score, failed notice, and mistakes list');

test('stage 1 quiz shows expected visual feedback for correct and wrong option selections', function () {
    //
})->skip('Practice: assert correct/wrong option feedback states (or fixed-seed assertScreenshotMatches())');

test('member completes stage 2 review quiz with perfect score using correct-option', function () {
    //
})->skip('Practice: answer all 20 stage 2 review questions via @correct-option and verify 100 / 100 & stage progression');

test('stage 2 review quiz result score matches partial correct count and lists mistakes', function () {
    //
})->skip('Practice: mix @wrong-option and @correct-option answers and assert score, threshold notice, and mistakes list');

test('stage 2 review quiz shows expected visual feedback for correct and wrong option selections', function () {
    //
})->skip('Practice: assert correct/wrong option feedback states (or fixed-seed assertScreenshotMatches())');
