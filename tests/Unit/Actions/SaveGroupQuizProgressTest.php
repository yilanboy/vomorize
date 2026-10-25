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
