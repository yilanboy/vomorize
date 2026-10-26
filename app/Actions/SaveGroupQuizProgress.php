<?php

namespace App\Actions;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

readonly class SaveGroupQuizProgress
{
    /**
     * @var array<int, int|null>
     */
    private const array STAGE_COOLDOWN_HOURS = [
        1 => 12,
        2 => 24,
        3 => 48,
        4 => 96,
        5 => 168,
        6 => null,
    ];

    public function handle(User $user, Group $group, int $score, ?CarbonInterface $now = null): LearningProgress
    {
        $now ??= now();

        return DB::transaction(function () use ($user, $group, $score, $now): LearningProgress {
            /** @var LearningProgress|null $existing */
            $existing = $user->learningProgress()
                ->where('group_id', $group->id)
                ->first();

            $currentStage = $existing instanceof LearningProgress ? $existing->stage : 0;

            if ($existing instanceof LearningProgress && $currentStage >= LearningProgress::STAGE_MASTERED) {
                $existing->update([
                    'last_score' => max($existing->last_score, $score),
                    'last_reviewed_at' => $now,
                    'next_review_at' => null,
                ]);

                $this->recordTodayCheckIn($user, $now);

                return $existing;
            }

            $targetStage = min(6, $currentStage + 1);
            $passThreshold = $targetStage >= 6 ? 90 : 80;
            $isPassed = $score >= $passThreshold;

            $newStage = $isPassed ? $targetStage : $currentStage;
            $cooldownHours = $isPassed
                ? (self::STAGE_COOLDOWN_HOURS[$newStage] ?? null)
                : null;

            $nextReviewAt = $cooldownHours !== null
                ? $now->copy()->addHours($cooldownHours)
                : null;

            $progress = LearningProgress::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                ],
                [
                    'level_id' => $group->level_id,
                    'stage' => $newStage,
                    'last_score' => $score,
                    'last_reviewed_at' => $now,
                    'next_review_at' => $nextReviewAt,
                ]
            );

            $this->recordTodayCheckIn($user, $now);

            return $progress;
        });
    }

    private function recordTodayCheckIn(User $user, CarbonInterface $now): void
    {
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
