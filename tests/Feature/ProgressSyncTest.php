<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;

test('guests cannot sync learning progress', function () {
    $response = $this->postJson(route('progress.sync'), [
        'local_progress' => [],
    ]);

    $response->assertUnauthorized();
});

test('authenticated user can sync new local progress records into empty account', function () {
    $user = User::factory()->create();
    $level1 = Level::factory()->create();
    $level2 = Level::factory()->create();

    $group1 = Group::factory()->create(['level_id' => $level1->id, 'sequence' => 1]);
    $group2 = Group::factory()->create(['level_id' => $level2->id, 'sequence' => 1]);

    $reviewedAt = now()->subHours(2)->startOfSecond();
    $nextReviewAt = now()->addHours(10)->startOfSecond();

    $response = $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('progress.sync'), [
            'local_progress' => [
                [
                    'group_id' => $group1->id,
                    'level_id' => $level2->id, // intentionally mismatched to verify server resolves true level_id from group
                    'stage' => 2,
                    'last_score' => 95,
                    'last_reviewed_at' => $reviewedAt->toIso8601String(),
                    'next_review_at' => $nextReviewAt->toIso8601String(),
                ],
                [
                    'group_id' => $group2->id,
                    'level_id' => $level2->id,
                    'stage' => 6,
                    'last_score' => 100,
                    'last_reviewed_at' => $reviewedAt->toIso8601String(),
                    'next_review_at' => null,
                ],
            ],
        ]);

    $response->assertRedirect(route('dashboard'));

    $progress1 = $user->learningProgress()->where('group_id', $group1->id)->first();
    expect($progress1)->not->toBeNull()
        ->and($progress1->level_id)->toBe($level1->id)
        ->and($progress1->stage)->toBe(2)
        ->and($progress1->last_score)->toBe(95)
        ->and($progress1->last_reviewed_at->equalTo($reviewedAt))->toBeTrue()
        ->and($progress1->next_review_at?->equalTo($nextReviewAt))->toBeTrue();

    $progress2 = $user->learningProgress()->where('group_id', $group2->id)->first();
    expect($progress2)->not->toBeNull()
        ->and($progress2->level_id)->toBe($level2->id)
        ->and($progress2->stage)->toBe(6)
        ->and($progress2->last_score)->toBe(100)
        ->and($progress2->next_review_at)->toBeNull();
});

test('conflict resolution overwrites only when local stage is strictly higher and keeps remote when stage is equal or lower', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();

    $groupHigherLocal = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);
    $groupLowerLocal = Group::factory()->create(['level_id' => $level->id, 'sequence' => 2]);
    $groupEqualStage = Group::factory()->create(['level_id' => $level->id, 'sequence' => 3]);

    $yesterday = now()->subDay()->startOfSecond();
    $now = now()->startOfSecond();

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groupHigherLocal->id,
        'stage' => 1,
        'last_score' => 80,
        'last_reviewed_at' => $yesterday,
    ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groupLowerLocal->id,
        'stage' => 4,
        'last_score' => 90,
        'last_reviewed_at' => $yesterday,
    ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groupEqualStage->id,
        'stage' => 2,
        'last_score' => 85,
        'last_reviewed_at' => $yesterday,
    ]);

    $response = $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('progress.sync'), [
            'local_progress' => [
                [
                    'group_id' => $groupHigherLocal->id,
                    'level_id' => $level->id,
                    'stage' => 3,
                    'last_score' => 95,
                    'last_reviewed_at' => $now->toIso8601String(),
                    'next_review_at' => $now->copy()->addDays(2)->toIso8601String(),
                ],
                [
                    'group_id' => $groupLowerLocal->id,
                    'level_id' => $level->id,
                    'stage' => 2,
                    'last_score' => 100,
                    'last_reviewed_at' => $now->toIso8601String(),
                    'next_review_at' => $now->copy()->addDay()->toIso8601String(),
                ],
                [
                    'group_id' => $groupEqualStage->id,
                    'level_id' => $level->id,
                    'stage' => 2,
                    'last_score' => 100,
                    'last_reviewed_at' => $now->toIso8601String(),
                    'next_review_at' => $now->copy()->addDay()->toIso8601String(),
                ],
            ],
        ]);

    $response->assertRedirect(route('dashboard'));

    $higher = $user->learningProgress()->where('group_id', $groupHigherLocal->id)->first();
    expect($higher?->stage)->toBe(3)
        ->and($higher?->last_score)->toBe(95);

    $lower = $user->learningProgress()->where('group_id', $groupLowerLocal->id)->first();
    expect($lower?->stage)->toBe(4)
        ->and($lower?->last_score)->toBe(90);

    $equal = $user->learningProgress()->where('group_id', $groupEqualStage->id)->first();
    expect($equal?->stage)->toBe(2)
        ->and($equal?->last_score)->toBe(85)
        ->and($equal?->last_reviewed_at->equalTo($yesterday))->toBeTrue();
});

test('clamps future last_reviewed_at beyond 5 minutes of clock skew to current server time', function () {
    $this->freezeTime();

    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    $futureReviewedAt = now()->addHours(2);

    $response = $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('progress.sync'), [
            'local_progress' => [
                [
                    'group_id' => $group->id,
                    'level_id' => $level->id,
                    'stage' => 1,
                    'last_score' => 85,
                    'last_reviewed_at' => $futureReviewedAt->toIso8601String(),
                    'next_review_at' => $futureReviewedAt->copy()->addHours(12)->toIso8601String(),
                ],
            ],
        ]);

    $response->assertRedirect(route('dashboard'));

    $progress = $user->learningProgress()->where('group_id', $group->id)->first();
    expect($progress?->last_reviewed_at->equalTo(now()->startOfSecond()))->toBeTrue();
});

test('updates user daily streak when synced progress contains a review from today', function () {
    $this->freezeTime();

    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    // 1. Brand new user syncing today's study record gets streak = 1
    $newUser = User::factory()->create([
        'current_streak' => 0,
        'longest_streak' => 0,
        'last_studied_date' => null,
    ]);

    $this->actingAs($newUser)
        ->from(route('dashboard'))
        ->post(route('progress.sync'), [
            'local_progress' => [
                [
                    'group_id' => $group->id,
                    'level_id' => $level->id,
                    'stage' => 1,
                    'last_score' => 90,
                    'last_reviewed_at' => now()->subMinutes(10)->toIso8601String(),
                    'next_review_at' => now()->addHours(12)->toIso8601String(),
                ],
            ],
        ])->assertRedirect(route('dashboard'));

    $newUser->refresh();
    expect($newUser->current_streak)->toBe(1)
        ->and($newUser->longest_streak)->toBe(1)
        ->and($newUser->last_studied_date?->isToday())->toBeTrue();

    // 2. Existing user who checked in yesterday increments streak when syncing today's record
    $continuingUser = User::factory()->create([
        'current_streak' => 3,
        'longest_streak' => 3,
        'last_studied_date' => now()->subDay(),
    ]);

    $this->actingAs($continuingUser)
        ->from(route('dashboard'))
        ->post(route('progress.sync'), [
            'local_progress' => [
                [
                    'group_id' => $group->id,
                    'level_id' => $level->id,
                    'stage' => 1,
                    'last_score' => 90,
                    'last_reviewed_at' => now()->toIso8601String(),
                    'next_review_at' => now()->addHours(12)->toIso8601String(),
                ],
            ],
        ])->assertRedirect(route('dashboard'));

    $continuingUser->refresh();
    expect($continuingUser->current_streak)->toBe(4)
        ->and($continuingUser->longest_streak)->toBe(4)
        ->and($continuingUser->last_studied_date?->isToday())->toBeTrue();

    // 3. Syncing again on the same day does not double-increment streak
    $this->actingAs($continuingUser)
        ->from(route('dashboard'))
        ->post(route('progress.sync'), [
            'local_progress' => [
                [
                    'group_id' => $group->id,
                    'level_id' => $level->id,
                    'stage' => 2,
                    'last_score' => 95,
                    'last_reviewed_at' => now()->toIso8601String(),
                    'next_review_at' => now()->addDay()->toIso8601String(),
                ],
            ],
        ])->assertRedirect(route('dashboard'));

    $continuingUser->refresh();
    expect($continuingUser->current_streak)->toBe(4);
});

test('validates sync payload structure and field bounds', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    // Empty local_progress is rejected
    $this->actingAs($user)
        ->postJson(route('progress.sync'), ['local_progress' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['local_progress']);

    // Out-of-bounds stage and last_score, and non-existent group_id are rejected
    $this->actingAs($user)
        ->postJson(route('progress.sync'), [
            'local_progress' => [
                [
                    'group_id' => 999999,
                    'level_id' => $level->id,
                    'stage' => 7,
                    'last_score' => 105,
                    'last_reviewed_at' => 'not-a-date',
                ],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'local_progress.0.group_id',
            'local_progress.0.stage',
            'local_progress.0.last_score',
            'local_progress.0.last_reviewed_at',
        ]);
});
