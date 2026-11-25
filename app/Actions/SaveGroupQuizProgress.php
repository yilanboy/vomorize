<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

readonly class SaveGroupQuizProgress
{
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

                return $existing;
            }

            $targetStage = min(LearningProgress::STAGE_MASTERED, $currentStage + 1);
            $passThreshold = $targetStage >= LearningProgress::STAGE_MASTERED ? 90 : 80;
            $isPassed = $score >= $passThreshold;

            $newStage = $isPassed ? $targetStage : $currentStage;
            $cooldownHours = $isPassed
                ? (LearningProgress::STAGE_COOLDOWN_HOURS[$newStage] ?? null)
                : (LearningProgress::STAGE_FAILURE_COOLDOWN_HOURS[$targetStage] ?? null);

            $nextReviewAt = $cooldownHours !== null
                ? $now->copy()->addHours($cooldownHours)
                : null;

            return LearningProgress::query()->updateOrCreate(
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
        });
    }
}
