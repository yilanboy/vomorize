<?php

use App\Services\SpacedRepetitionService;
use Illuminate\Support\Carbon;

it('advances stage on pass score >= 90', function () {
    $now = Carbon::parse('2026-07-24 12:00:00', 'UTC');

    // Stage 0 -> Stage 1 (12 hours)
    $res0 = SpacedRepetitionService::calculateNextState(0, 90, $now);
    expect($res0['stage'])->toBe(1)
        ->and($res0['passed'])->toBeTrue()
        ->and($res0['next_review_at']->toIso8601String())->toBe('2026-07-25T00:00:00+00:00');

    // Stage 1 -> Stage 2 (1 day)
    $res1 = SpacedRepetitionService::calculateNextState(1, 90, $now);
    expect($res1['stage'])->toBe(2)
        ->and($res1['next_review_at']->toIso8601String())->toBe('2026-07-25T12:00:00+00:00');

    // Stage 5 -> Stage 6 (completed, next_review_at is null)
    $res5 = SpacedRepetitionService::calculateNextState(5, 100, $now);
    expect($res5['stage'])->toBe(6)
        ->and($res5['next_review_at'])->toBeNull();
});

it('applies penalty lock on fail score < 90 without advancing stage', function () {
    $now = Carbon::parse('2026-07-24 12:00:00', 'UTC');

    // Stage 0 fail -> 12 hours penalty lock
    $res0 = SpacedRepetitionService::calculateNextState(0, 50, $now);
    expect($res0['stage'])->toBe(0)
        ->and($res0['passed'])->toBeFalse()
        ->and($res0['next_review_at']->toIso8601String())->toBe('2026-07-25T00:00:00+00:00');

    // Stage 2 fail -> 1 day penalty lock
    $res2 = SpacedRepetitionService::calculateNextState(2, 40, $now);
    expect($res2['stage'])->toBe(2)
        ->and($res2['passed'])->toBeFalse()
        ->and($res2['next_review_at']->toIso8601String())->toBe('2026-07-25T12:00:00+00:00');

    // Stage 6 fail -> no lock
    $res6 = SpacedRepetitionService::calculateNextState(6, 30, $now);
    expect($res6['stage'])->toBe(6)
        ->and($res6['passed'])->toBeFalse()
        ->and($res6['next_review_at'])->toBeNull();
});

it('derives group status correctly', function () {
    $now = Carbon::parse('2026-07-24 12:00:00', 'UTC');
    $future = Carbon::parse('2026-07-25 12:00:00', 'UTC');
    $past = Carbon::parse('2026-07-23 12:00:00', 'UTC');

    expect(SpacedRepetitionService::deriveStatus(0, null, null, $now))->toBe('not_started');
    expect(SpacedRepetitionService::deriveStatus(0, 50, $future, $now))->toBe('penalty');
    expect(SpacedRepetitionService::deriveStatus(2, 80, $future, $now))->toBe('locked');
    expect(SpacedRepetitionService::deriveStatus(2, 80, $past, $now))->toBe('ready');
    expect(SpacedRepetitionService::deriveStatus(6, 100, null, $now))->toBe('completed');
});
