<?php

namespace App\Actions;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

readonly class GetActionCenterQueue
{
    /**
     * @param  Collection<int, LearningProgress>  $userProgress
     * @return array{
     *     due_reviews: array<int, array{
     *         group_id: int,
     *         level_id: int,
     *         group_title: string,
     *         words_count: int,
     *         stage: int,
     *         target_stage: int,
     *         badge_text: string,
     *         badge_variant: string,
     *         last_score: int|null,
     *         status_hint: string,
     *         review_url: string,
     *     }>,
     *     upcoming_reviews: array<int, array{
     *         group_id: int,
     *         level_id: int,
     *         group_title: string,
     *         stage_label: string,
     *         next_review_at: string,
     *         cooldown_remaining_seconds: int,
     *     }>,
     *     recommended_next_group: array{
     *         group_id: int,
     *         level_id: int,
     *         group_sequence: int,
     *         group_title: string,
     *         words_count: int,
     *         start_url: string,
     *     }|null,
     * }
     */
    public function handle(User $user, Collection $userProgress, CarbonInterface $now): array
    {
        $dueReviews = [];
        $upcomingReviews = [];

        foreach ($userProgress as $progress) {
            $stage = $progress->stage;

            if ($stage >= 6) {
                continue;
            }

            $isFailed = $progress->last_score < 80;
            $isCooldownElapsed = $progress->next_review_at === null || $progress->next_review_at->lte($now);

            $levelId = $progress->level_id;
            $sequence = $progress->group !== null ? $progress->group->sequence : $progress->group_id;
            $groupTitle = "等級 {$levelId} · 第 {$sequence} 組";

            if ($isFailed || $isCooldownElapsed) {
                $targetStage = $isFailed ? max(1, $stage) : min(6, $stage + 1);

                if ($isFailed) {
                    $badgeText = "重測 Stage {$targetStage}";
                    $badgeVariant = 'warning';
                    $statusHint = '上次得分：'.$progress->last_score.' 分 (未及格)';
                } elseif ($targetStage >= 6) {
                    $badgeText = '終極驗收 Stage 6';
                    $badgeVariant = 'success';
                    $statusHint = '即將達成永久記憶';
                } else {
                    $badgeText = "挑戰 Stage {$targetStage}";
                    $badgeVariant = 'primary';
                    $statusHint = '冷卻完畢';
                }

                $dueReviews[] = [
                    'group_id' => $progress->group_id,
                    'level_id' => $levelId,
                    'group_title' => $groupTitle,
                    'words_count' => 10,
                    'stage' => $stage,
                    'target_stage' => $targetStage,
                    'badge_text' => $badgeText,
                    'badge_variant' => $badgeVariant,
                    'last_score' => $progress->last_score,
                    'status_hint' => $statusHint,
                    'review_url' => "/groups/{$progress->group_id}",
                    'is_failed' => $isFailed,
                    'next_review_at' => $progress->next_review_at,
                ];
            } elseif ($progress->next_review_at !== null) {
                $nextReviewAt = $progress->next_review_at;

                $upcomingReviews[] = [
                    'group_id' => $progress->group_id,
                    'level_id' => $levelId,
                    'group_title' => $groupTitle,
                    'stage_label' => '進階 Stage '.min(6, $stage + 1),
                    'next_review_at' => $nextReviewAt->toIso8601String(),
                    'cooldown_remaining_seconds' => max(0, (int) $now->diffInSeconds($nextReviewAt, false)),
                    'raw_next_review_at' => $nextReviewAt,
                ];
            }
        }

        // Sort due reviews: failed first, then next_review_at asc
        usort($dueReviews, function ($a, $b) {
            if ($a['is_failed'] !== $b['is_failed']) {
                return $a['is_failed'] ? -1 : 1;
            }

            if ($a['next_review_at'] && $b['next_review_at']) {
                return $a['next_review_at']->timestamp <=> $b['next_review_at']->timestamp;
            }

            return 0;
        });

        // Strip helper keys from dueReviews
        $cleanDueReviews = array_map(function ($item) {
            unset($item['is_failed'], $item['next_review_at']);

            return $item;
        }, $dueReviews);

        // Sort upcoming reviews: next_review_at asc, limit 3
        usort($upcomingReviews, function ($a, $b) {
            return $a['raw_next_review_at']->timestamp <=> $b['raw_next_review_at']->timestamp;
        });

        $cleanUpcomingReviews = array_slice(array_map(function ($item) {
            unset($item['raw_next_review_at']);

            return $item;
        }, $upcomingReviews), 0, 3);

        // Find next unstarted group
        $startedGroupIds = $userProgress->pluck('group_id')->all();

        /** @var Group|null $nextGroup */
        $nextGroup = Group::query()
            ->when(! empty($startedGroupIds), fn ($query) => $query->whereNotIn('id', $startedGroupIds))
            ->orderBy('level_id')
            ->orderBy('sequence')
            ->first();

        $recommendedNextGroup = null;

        if ($nextGroup !== null) {
            $recommendedNextGroup = [
                'group_id' => $nextGroup->id,
                'level_id' => $nextGroup->level_id,
                'group_sequence' => $nextGroup->sequence,
                'group_title' => "等級 {$nextGroup->level_id} · 第 {$nextGroup->sequence} 組",
                'words_count' => 10,
                'start_url' => "/groups/{$nextGroup->id}",
            ];
        }

        return [
            'due_reviews' => $cleanDueReviews,
            'upcoming_reviews' => $cleanUpcomingReviews,
            'recommended_next_group' => $recommendedNextGroup,
        ];
    }
}
