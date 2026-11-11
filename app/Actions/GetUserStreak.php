<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

readonly class GetUserStreak
{
    /**
     * @return array{
     *     current: int,
     *     longest: int,
     *     is_checked_in_today: bool,
     * }
     */
    public function handle(User $user): array
    {
        $isCheckedInToday = $user->last_studied_date !== null ? $user->last_studied_date->isToday() : false;

        return [
            'current' => $user->current_streak ?? 0,
            'longest' => $user->longest_streak ?? 0,
            'is_checked_in_today' => $isCheckedInToday,
        ];
    }
}
