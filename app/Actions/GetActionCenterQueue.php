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

            $isStage6Failed = $stage === 5 && $progress->next_review_at === null && $progress->last_score < 90;
            $isFailed = $progress->last_score < 80 || $isStage6Failed;
            $isCooldownElapsed = $progress->next_review_at === null || $progress->next_review_at->lte($now);

            $levelId = $progress->level_id;
            $levelName = $progress->group !== null ? $progress->group->level->name : "等級 {$levelId}";
            $sequence = $progress->group !== null ? $progress->group->sequence : $progress->group_id;
            $groupTitle = (string) __('ui.group_detail.title', [
                'level_name' => $levelName,
                'level_id' => $levelId,
                'sequence' => $sequence,
            ]);

            if ($isFailed || $isCooldownElapsed) {
                $targetStage = $isStage6Failed ? 6 : ($isFailed ? max(1, $stage) : min(6, $stage + 1));

                if ($isFailed) {
                    $badgeText = (string) __('ui.dashboard.action_center.badge_retake', ['stage' => $targetStage]);
                    $badgeVariant = 'warning';
                    $statusHint = (string) __('ui.dashboard.action_center.hint_failed', ['score' => $progress->last_score]);
                } elseif ($targetStage >= 6) {
                    $badgeText = (string) __('ui.dashboard.action_center.badge_final', ['stage' => 6]);
                    $badgeVariant = 'success';
                    $statusHint = (string) __('ui.dashboard.action_center.hint_permanent_memory');
                } else {
                    $badgeText = (string) __('ui.dashboard.action_center.badge_challenge', ['stage' => $targetStage]);
                    $badgeVariant = 'primary';
                    $statusHint = (string) __('ui.dashboard.action_center.hint_cooldown_elapsed');
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
                    'stage_label' => (string) __('ui.dashboard.action_center.stage_advance_label', ['stage' => min(6, $stage + 1)]),
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
            ->with('level')
            ->when(! empty($startedGroupIds), fn ($query) => $query->whereNotIn('id', $startedGroupIds))
            ->orderBy('level_id')
            ->orderBy('sequence')
            ->first();

        $recommendedNextGroup = null;

        if ($nextGroup !== null) {
            $nextGroupLevelName = $nextGroup->level->name;
            $recommendedNextGroup = [
                'group_id' => $nextGroup->id,
                'level_id' => $nextGroup->level_id,
                'group_sequence' => $nextGroup->sequence,
                'group_title' => (string) __('ui.group_detail.title', [
                    'level_name' => $nextGroupLevelName,
                    'level_id' => $nextGroup->level_id,
                    'sequence' => $nextGroup->sequence,
                ]),
                'words_count' => 10,
                'start_url' => "/groups/{$nextGroup->id}/intro",
            ];
        }

        return [
            'due_reviews' => $cleanDueReviews,
            'upcoming_reviews' => $cleanUpcomingReviews,
            'recommended_next_group' => $recommendedNextGroup,
        ];
    }
}
