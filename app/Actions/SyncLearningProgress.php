<?php

namespace App\Actions;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

readonly class SyncLearningProgress
{
    /**
     * @param  array<int, array{
     *     group_id: int,
     *     level_id?: int,
     *     stage: int,
     *     last_score: int,
     *     last_reviewed_at: string,
     *     next_review_at?: string|null,
     * }>  $localProgress
     */
    public function handle(User $user, array $localProgress): int
    {
        return DB::transaction(function () use ($user, $localProgress): int {
            $groupIds = array_column($localProgress, 'group_id');

            /** @var array<int, int> $groupLevels */
            $groupLevels = Group::query()
                ->whereIn('id', $groupIds)
                ->pluck('level_id', 'id')
                ->all();

            /** @var Collection<int, LearningProgress> $existingProgress */
            $existingProgress = $user->learningProgress()
                ->whereIn('group_id', $groupIds)
                ->get()
                ->keyBy('group_id');

            $syncedCount = 0;
            $hasStudiedToday = false;
            $now = now();
            $maxAllowedReviewedAt = $now->copy()->addMinutes(5);

            foreach ($localProgress as $item) {
                $groupId = (int) $item['group_id'];
                if (! isset($groupLevels[$groupId])) {
                    continue;
                }

                $levelId = (int) $groupLevels[$groupId];
                $localStage = (int) $item['stage'];
                $lastScore = (int) $item['last_score'];
                $lastReviewedAt = Carbon::parse($item['last_reviewed_at']);
                if ($lastReviewedAt->greaterThan($maxAllowedReviewedAt)) {
                    $lastReviewedAt = $now->copy();
                }

                if ($lastReviewedAt->isToday()) {
                    $hasStudiedToday = true;
                }

                $nextReviewAt = isset($item['next_review_at'])
                    ? Carbon::parse($item['next_review_at'])
                    : null;

                /** @var LearningProgress|null $remote */
                $remote = $existingProgress->get($groupId);

                if ($remote === null) {
                    $user->learningProgress()->create([
                        'level_id' => $levelId,
                        'group_id' => $groupId,
                        'stage' => $localStage,
                        'last_score' => $lastScore,
                        'last_reviewed_at' => $lastReviewedAt,
                        'next_review_at' => $nextReviewAt,
                    ]);

                    $syncedCount++;

                    continue;
                }

                if ($localStage > $remote->stage) {
                    $remote->update([
                        'level_id' => $levelId,
                        'stage' => $localStage,
                        'last_score' => $lastScore,
                        'last_reviewed_at' => $lastReviewedAt,
                        'next_review_at' => $nextReviewAt,
                    ]);

                    $syncedCount++;
                }
            }

            if ($hasStudiedToday) {
                $this->recordTodayCheckIn($user, $now);
            }

            return $syncedCount;
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
