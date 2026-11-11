<?php

declare(strict_types=1);

use App\Actions\GetUserStreak;
use App\Models\User;

it('returns the user streak', function () {
    $user = User::factory()->create([
        'current_streak' => 5,
        'longest_streak' => 10,
        'last_studied_date' => now(),
    ]);

    $getUserStreak = app()->make(GetUserStreak::class);
    $streak = $getUserStreak->handle($user);

    expect($streak['current'])
        ->toBe(5)
        ->and($streak['longest'])
        ->toBe(10)
        ->and($streak['is_checked_in_today'])
        ->toBe(true);
});

it('return false when not checked in today', function () {
    $user = User::factory()->create([
        'current_streak' => 5,
        'longest_streak' => 10,
        'last_studied_date' => now()->subDays(1),
    ]);

    $getUserStreak = app()->make(GetUserStreak::class);
    $streak = $getUserStreak->handle($user);

    expect($streak['is_checked_in_today'])
        ->toBe(false);
});
