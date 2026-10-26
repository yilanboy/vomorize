<?php

namespace App\Actions;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

readonly class GetDashboardKpis
{
    /**
     * @param  Collection<int, LearningProgress>  $userProgress
     * @return array{
     *     streak: array{
     *         current: int,
     *         longest: int,
     *         is_checked_in_today: bool,
     *     },
     *     learning_words: array{
     *         words_count: int,
     *         groups_count: int,
     *     },
     *     mastered_words: array{
     *         words_count: int,
     *         groups_count: int,
     *         percentage: float,
     *     },
     *     due_groups: array{
     *         count: int,
     *         has_due: bool,
     *     },
     * }
     */
    public function handle(User $user, Collection $userProgress, CarbonInterface $now): array
    {
        $learningGroupsCount = 0;
        $masteredGroupsCount = 0;
        $dueGroupsCount = 0;

        foreach ($userProgress as $progress) {
            $stage = $progress->stage;

            if ($stage >= 6) {
                $masteredGroupsCount++;
            } elseif ($stage >= 1) {
                $learningGroupsCount++;
            }

            if ($stage < 6) {
                $isFailed =
                    $progress->last_score < 80
                    || $stage === 5 && $progress->next_review_at === null && $progress->last_score < 90;
                $isCooldownElapsed = $progress->next_review_at === null || $progress->next_review_at->lte($now);

                if ($isFailed || $isCooldownElapsed) {
                    $dueGroupsCount++;
                }
            }
        }

        $totalGroupsCount = Group::query()->count();
        $totalGroupsCount = $totalGroupsCount > 0 ? $totalGroupsCount : 700;

        $masteredPercentage = round(($masteredGroupsCount / $totalGroupsCount) * 100, 1);

        $isCheckedInToday = $user->last_studied_date !== null && $user->last_studied_date->isToday();

        return [
            'streak' => [
                'current' => $user->current_streak ?? 0,
                'longest' => $user->longest_streak ?? 0,
                'is_checked_in_today' => $isCheckedInToday,
            ],
            'learning_words' => [
                'words_count' => $learningGroupsCount * 10,
                'groups_count' => $learningGroupsCount,
            ],
            'mastered_words' => [
                'words_count' => $masteredGroupsCount * 10,
                'groups_count' => $masteredGroupsCount,
                'percentage' => $masteredPercentage,
            ],
            'due_groups' => [
                'count' => $dueGroupsCount,
                'has_due' => $dueGroupsCount > 0,
            ],
        ];
    }
}
