<?php

namespace App\Actions;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

readonly class RecordTodayCheckIn
{
    /**
     * Record daily study check-in and update consecutive streaks.
     */
    public function handle(User $user, ?CarbonInterface $now = null): void
    {
        $now = $now ?? Carbon::now();

        if ($user->last_studied_date !== null && $user->last_studied_date->isToday()) {
            return;
        }

        $newCurrentStreak = ($user->last_studied_date !== null && $user->last_studied_date->isYesterday())
            ? $user->current_streak + 1
            : 1;

        $user->update([
            'current_streak' => $newCurrentStreak,
            'longest_streak' => max($user->longest_streak, $newCurrentStreak),
            'last_studied_date' => $now,
        ]);
    }
}
