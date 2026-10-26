<?php

use App\Actions\DetermineGroupStatus;
use App\Enums\GroupStatus;
use App\Models\LearningProgress;
use Tests\TestCase;

uses(TestCase::class);

test('returns not_started when progress is null', function () {
    $action = new DetermineGroupStatus;
    $result = $action->handle(null);

    expect($result['status'])->toBe(GroupStatus::NotStarted)->and($result['cooldown_remaining_seconds'])->toBeNull();
});

test('returns not_started when stage is 0', function () {
    $progress = new LearningProgress(['stage' => LearningProgress::STAGE_UNSTARTED]);
    $action = new DetermineGroupStatus;
    $result = $action->handle($progress);

    expect($result['status'])->toBe(GroupStatus::NotStarted)->and($result['cooldown_remaining_seconds'])->toBeNull();
});

test('returns completed when stage is 6 or greater', function () {
    $progress = new LearningProgress(['stage' => LearningProgress::STAGE_MASTERED]);
    $action = new DetermineGroupStatus;
    $result = $action->handle($progress);

    expect($result['status'])->toBe(GroupStatus::Completed)->and($result['cooldown_remaining_seconds'])->toBeNull();
});

test('returns cooldown with remaining seconds when next_review_at is in future', function () {
    $now = now()->startOfSecond();
    $futureTime = $now->copy()->addMinutes(30);

    $progress = new LearningProgress([
        'stage' => 2,
        'next_review_at' => $futureTime,
    ]);

    $action = new DetermineGroupStatus;
    $result = $action->handle($progress, $now);

    expect($result['status'])->toBe(GroupStatus::Cooldown)->and($result['cooldown_remaining_seconds'])->toBe(1800);
});

test('returns ready when stage is between 1 and 5 and next_review_at is in past', function () {
    $now = now()->startOfSecond();
    $pastTime = $now->copy()->subMinutes(10);

    $progress = new LearningProgress([
        'stage' => 3,
        'next_review_at' => $pastTime,
    ]);

    $action = new DetermineGroupStatus;
    $result = $action->handle($progress, $now);

    expect($result['status'])->toBe(GroupStatus::Ready)->and($result['cooldown_remaining_seconds'])->toBeNull();
});

test('returns ready when stage is between 1 and 5 and next_review_at is null', function () {
    $progress = new LearningProgress([
        'stage' => 1,
        'next_review_at' => null,
    ]);

    $action = new DetermineGroupStatus;
    $result = $action->handle($progress);

    expect($result['status'])->toBe(GroupStatus::Ready)->and($result['cooldown_remaining_seconds'])->toBeNull();
});
