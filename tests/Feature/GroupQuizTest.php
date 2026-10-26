<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guest can access group review quiz page and receives 20-step session payload', function () {
    $level = Level::factory()->create(['name' => '基礎入門']);
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $response = $this->get(route('groups.quiz', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('groups/Quiz')
        ->has('group')
        ->where('group.id', $group->id)
        ->where('progress', null)
        ->has('session.steps', 20)
        ->has('session.correct_audio_url')
        ->has('session.wrong_audio_url')
    );
});

test('unstarted member accessing group review quiz is redirected to group intro', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    // No progress record
    $this->actingAs($user)
        ->get(route('groups.quiz', $group))
        ->assertRedirect(route('groups.intro', $group));

    // Stage 0 progress record
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 0,
        'last_score' => 70,
    ]);

    $this->actingAs($user)
        ->get(route('groups.quiz', $group))
        ->assertRedirect(route('groups.intro', $group));
});

test('member whose group is in cooldown accessing group review quiz is redirected to group show', function () {
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
        'stage' => 2,
        'last_score' => 85,
        'next_review_at' => now()->addHours(10),
    ]);

    $this->actingAs($user)
        ->get(route('groups.quiz', $group))
        ->assertRedirect(route('groups.show', $group));
});

test('member can progress through stages 2 to 6 and mark group as completed', function () {
    Carbon::setTestNow('2026-10-25 08:00:00');

    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    // Start at Stage 1 with expired cooldown (ready for Stage 2)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 85,
        'next_review_at' => now()->subMinute(),
    ]);

    // Access Stage 2 quiz
    $this->actingAs($user)
        ->get(route('groups.quiz', $group))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('groups/Quiz')
            ->where('progress.stage', 1)
            ->where('progress.status', 'ready')
            ->has('session.steps', 20)
        );

    // Complete Stage 2 -> 3 -> 4 -> 5
    $cooldownHours = [2 => 24, 3 => 48, 4 => 96, 5 => 168];

    foreach ($cooldownHours as $expectedStage => $hours) {
        $this->actingAs($user)
            ->from(route('groups.quiz', $group))
            ->post(route('groups.progress.save', $group), ['score' => 80])
            ->assertRedirect(route('groups.quiz', $group));

        $progress = LearningProgress::where('user_id', $user->id)->where('group_id', $group->id)->firstOrFail();
        expect($progress->stage)->toBe($expectedStage);

        // Advance time past cooldown
        Carbon::setTestNow(now()->addHours($hours)->addMinute());
    }

    // Now at Stage 5, ready for Stage 6 final review: fail with 85 (< 90)
    $this->actingAs($user)
        ->from(route('groups.quiz', $group))
        ->post(route('groups.progress.save', $group), ['score' => 85])
        ->assertRedirect(route('groups.quiz', $group));

    $progressAfterFail = LearningProgress::where('user_id', $user->id)->where('group_id', $group->id)->firstOrFail();
    expect($progressAfterFail->stage)->toBe(5)
        ->and($progressAfterFail->next_review_at)->toBeNull();

    // Pass Stage 6 with 95 (>= 90) -> group is marked completed!
    $this->actingAs($user)
        ->from(route('groups.quiz', $group))
        ->post(route('groups.progress.save', $group), ['score' => 95])
        ->assertRedirect(route('groups.quiz', $group));

    $completedProgress = LearningProgress::where('user_id', $user->id)->where('group_id', $group->id)->firstOrFail();
    expect($completedProgress->stage)->toBe(6)
        ->and($completedProgress->next_review_at)->toBeNull();

    // Verify group show reports status = completed
    $this->actingAs($user)
        ->get(route('groups.show', $group))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('groups/Show')
            ->where('progress.stage', 6)
            ->where('progress.status', 'completed')
        );

    // Completed group can still access /groups/{group}/quiz for self-testing and stays completed even if score is 50
    $this->actingAs($user)
        ->get(route('groups.quiz', $group))
        ->assertOk();

    $this->actingAs($user)
        ->from(route('groups.quiz', $group))
        ->post(route('groups.progress.save', $group), ['score' => 50])
        ->assertRedirect(route('groups.quiz', $group));

    $afterSelfTest = LearningProgress::where('user_id', $user->id)->where('group_id', $group->id)->firstOrFail();
    expect($afterSelfTest->stage)->toBe(6)
        ->and($afterSelfTest->last_score)->toBe(95)
        ->and($afterSelfTest->next_review_at)->toBeNull();
});
