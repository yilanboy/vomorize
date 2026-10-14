<?php

namespace App\Actions;

use App\Enums\GroupStatus;
use App\Models\LearningProgress;
use Carbon\CarbonInterface;

class DetermineGroupStatus
{
    /**
     * @return array{
     *     status: GroupStatus,
     *     cooldown_remaining_seconds: int<0, max>|null
     * }
     */
    public function handle(?LearningProgress $progress, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $stage = (int) ($progress?->stage ?? 0);

        if ($stage >= 6) {
            return [
                'status' => GroupStatus::Completed,
                'cooldown_remaining_seconds' => null,
            ];
        }

        if ($stage > 0) {
            if ($progress?->next_review_at && $progress->next_review_at->isAfter($now)) {
                return [
                    'status' => GroupStatus::Cooldown,
                    'cooldown_remaining_seconds' => max(0, (int) $now->diffInSeconds($progress->next_review_at, false)),
                ];
            }

            return [
                'status' => GroupStatus::Ready,
                'cooldown_remaining_seconds' => null,
            ];
        }

        return [
            'status' => GroupStatus::NotStarted,
            'cooldown_remaining_seconds' => null,
        ];
    }
}
