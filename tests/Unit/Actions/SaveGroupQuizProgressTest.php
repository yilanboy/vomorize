<?php

use App\Actions\SaveGroupQuizProgress;
use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('passing stage 1 with score 80 or above advances stage to 1 with 12 hour cooldown and updates streak', function () {
    Carbon::setTestNow('2026-10-25 10:00:00');

    $user = User::factory()->create([
        'current_streak' => 0,
        'longest_streak' => 0,
        'last_studied_date' => null,
    ]);
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $action = app(SaveGroupQuizProgress::class);
    $progress = $action->handle($user, $group, 85);

    expect($progress->user_id)->toBe($user->id)
        ->and($progress->group_id)->toBe($group->id)
        ->and($progress->level_id)->toBe($level->id)
        ->and($progress->stage)->toBe(1)
        ->and($progress->last_score)->toBe(85)
        ->and($progress->next_review_at?->toDateTimeString())->toBe('2026-10-25 22:00:00');

    $user->refresh();
    expect($user->current_streak)->toBe(1)
        ->and($user->longest_streak)->toBe(1)
        ->and($user->last_studied_date?->toDateString())->toBe('2026-10-25');
});

test('failing stage 1 with score below 80 keeps stage at 0 with null cooldown', function () {
    Carbon::setTestNow('2026-10-25 10:00:00');

    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $action = app(SaveGroupQuizProgress::class);
    $progress = $action->handle($user, $group, 70);

    expect($progress->stage)->toBe(0)
        ->and($progress->last_score)->toBe(70)
        ->and($progress->next_review_at)->toBeNull();
});

test('subsequent quiz updates existing learning progress record instead of creating duplicate', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $action = app(SaveGroupQuizProgress::class);

    // First attempt: fail
    $action->handle($user, $group, 60);
    expect(LearningProgress::where('user_id', $user->id)->where('group_id', $group->id)->count())->toBe(1);

    // Second attempt: pass
    $action->handle($user, $group, 90);
    expect(LearningProgress::where('user_id', $user->id)->where('group_id', $group->id)->count())->toBe(1);

    $final = LearningProgress::where('user_id', $user->id)->where('group_id', $group->id)->first();
    expect($final->stage)->toBe(1)
        ->and($final->last_score)->toBe(90);
});

test('stages 2 through 5 advance with 24h, 48h, 96h, and 168h cooldowns at 80 threshold', function () {
    Carbon::setTestNow('2026-10-25 10:00:00');

    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 80,
        'next_review_at' => Carbon::now()->subMinute(),
    ]);

    $action = app(SaveGroupQuizProgress::class);

    // Stage 1 -> 2 (24 hours)
    $p2 = $action->handle($user, $group, 80);
    expect($p2->stage)->toBe(2)
        ->and($p2->next_review_at?->toDateTimeString())->toBe('2026-10-26 10:00:00');

    // Stage 2 -> 3 (48 hours)
    Carbon::setTestNow('2026-10-26 11:00:00');
    $p3 = $action->handle($user, $group, 80);
    expect($p3->stage)->toBe(3)
        ->and($p3->next_review_at?->toDateTimeString())->toBe('2026-10-28 11:00:00');

    // Stage 3 -> 4 (96 hours)
    Carbon::setTestNow('2026-10-28 12:00:00');
    $p4 = $action->handle($user, $group, 80);
    expect($p4->stage)->toBe(4)
        ->and($p4->next_review_at?->toDateTimeString())->toBe('2026-11-01 12:00:00');

    // Stage 4 -> 5 (168 hours)
    Carbon::setTestNow('2026-11-01 13:00:00');
    $p5 = $action->handle($user, $group, 85);
    expect($p5->stage)->toBe(5)
        ->and($p5->next_review_at?->toDateTimeString())->toBe('2026-11-08 13:00:00');
});

test('stage 6 requires 90 points to complete and retains stage 6 permanently on subsequent self-tests', function () {
    Carbon::setTestNow('2026-11-10 10:00:00');

    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 5,
        'last_score' => 85,
        'next_review_at' => Carbon::now()->subHour(),
    ]);

    $action = app(SaveGroupQuizProgress::class);

    // 85 points is below Stage 6 threshold (90): stays at stage 5 with null cooldown
    $failedFinal = $action->handle($user, $group, 85);
    expect($failedFinal->stage)->toBe(5)
        ->and($failedFinal->last_score)->toBe(85)
        ->and($failedFinal->next_review_at)->toBeNull();

    // 90 points passes Stage 6: reaches stage 6 with null cooldown (permanent completion)
    $passedFinal = $action->handle($user, $group, 90);
    expect($passedFinal->stage)->toBe(6)
        ->and($passedFinal->last_score)->toBe(90)
        ->and($passedFinal->next_review_at)->toBeNull();

    // Self-test on completed group with lower score (60) retains stage 6 and highest score (90)
    $selfTestLow = $action->handle($user, $group, 60);
    expect($selfTestLow->stage)->toBe(6)
        ->and($selfTestLow->last_score)->toBe(90)
        ->and($selfTestLow->next_review_at)->toBeNull();

    // Self-test on completed group with higher score (100) updates last_score to 100
    $selfTestHigh = $action->handle($user, $group, 100);
    expect($selfTestHigh->stage)->toBe(6)
        ->and($selfTestHigh->last_score)->toBe(100)
        ->and($selfTestHigh->next_review_at)->toBeNull();
});
