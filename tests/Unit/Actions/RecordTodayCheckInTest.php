<?php

declare(strict_types=1);

use App\Actions\RecordTodayCheckIn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('first study session sets current and longest streak to 1', function () {
    Carbon::setTestNow('2026-10-25 10:00:00');

    $user = User::factory()->create([
        'current_streak' => 0,
        'longest_streak' => 0,
        'last_studied_date' => null,
    ]);

    $action = app(RecordTodayCheckIn::class);
    $action->handle($user);

    $user->refresh();
    expect($user->current_streak)->toBe(1)
        ->and($user->longest_streak)->toBe(1)
        ->and($user->last_studied_date?->toDateString())->toBe('2026-10-25');
});

test('studying on consecutive day increments streak and updates longest streak', function () {
    Carbon::setTestNow('2026-10-25 10:00:00');

    $user = User::factory()->create([
        'current_streak' => 3,
        'longest_streak' => 3,
        'last_studied_date' => Carbon::parse('2026-10-24 18:00:00'),
    ]);

    $action = app(RecordTodayCheckIn::class);
    $action->handle($user);

    $user->refresh();
    expect($user->current_streak)->toBe(4)
        ->and($user->longest_streak)->toBe(4)
        ->and($user->last_studied_date?->toDateString())->toBe('2026-10-25');
});

test('studying again on the same day does not change streak', function () {
    Carbon::setTestNow('2026-10-25 15:00:00');

    $user = User::factory()->create([
        'current_streak' => 5,
        'longest_streak' => 10,
        'last_studied_date' => Carbon::parse('2026-10-25 09:00:00'),
    ]);

    $action = app(RecordTodayCheckIn::class);
    $action->handle($user);

    $user->refresh();
    expect($user->current_streak)->toBe(5)
        ->and($user->longest_streak)->toBe(10)
        ->and($user->last_studied_date?->toDateTimeString())->toBe('2026-10-25 09:00:00');
});

test('studying after missing a day resets current streak to 1 while preserving longest streak', function () {
    Carbon::setTestNow('2026-10-25 10:00:00');

    $user = User::factory()->create([
        'current_streak' => 7,
        'longest_streak' => 14,
        'last_studied_date' => Carbon::parse('2026-10-23 20:00:00'),
    ]);

    $action = app(RecordTodayCheckIn::class);
    $action->handle($user);

    $user->refresh();
    expect($user->current_streak)->toBe(1)
        ->and($user->longest_streak)->toBe(14)
        ->and($user->last_studied_date?->toDateString())->toBe('2026-10-25');
});
