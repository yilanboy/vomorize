<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Group;
use App\Models\LearningProgress;
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
     *         stage: int,
     *         target_stage: int,
     *         badge_text: string,
     *         badge_variant: string,
     *         last_score: int|null,
     *         is_failed: bool,
     *         review_url: string,
     *     }>,
     *     recommended_next_group: array{
     *         group_id: int,
     *         level_id: int,
     *         group_sequence: int,
     *         group_title: string,
     *         start_url: string,
     *     }|null,
     * }
     */
    public function handle(Collection $userProgress, CarbonInterface $now): array
    {
        $dueReviews = [];

        foreach ($userProgress as $progress) {
            $stage = $progress->stage;

            if ($stage >= LearningProgress::STAGE_MASTERED) {
                continue;
            }

            $isCooldownElapsed = $progress->next_review_at === null || $progress->next_review_at->lte($now);

            if (! $isCooldownElapsed) {
                continue;
            }

            $stage6RetryHours = LearningProgress::STAGE_FAILURE_COOLDOWN_HOURS[LearningProgress::STAGE_MASTERED];
            $isStage6RetryWindow = $progress->next_review_at === null
                || $progress->last_reviewed_at->diffInHours($progress->next_review_at, false) <= $stage6RetryHours;

            $isStage6Failed = $stage === (LearningProgress::STAGE_MASTERED - 1)
                && $progress->last_score < 90
                && $isStage6RetryWindow;

            $isFailed = $progress->last_score < 80 || $isStage6Failed;

            $levelId = $progress->level_id;
            $levelName = $progress->group !== null ? $progress->group->level->name : "等級 {$levelId}";
            $sequence = $progress->group !== null ? $progress->group->sequence : $progress->group_id;
            $groupTitle = (string) __('ui.group_detail.title', [
                'level_name' => $levelName,
                'level_id' => $levelId,
                'sequence' => $sequence,
            ]);

            $targetStage = min(LearningProgress::STAGE_MASTERED, $stage + 1);

            if ($isFailed) {
                $badgeText = (string) __('ui.dashboard.action_center.badge_retake', ['stage' => $targetStage]);
                $badgeVariant = 'warning';
            } elseif ($targetStage >= LearningProgress::STAGE_MASTERED) {
                $badgeText = (string) __('ui.dashboard.action_center.badge_final', ['stage' => LearningProgress::STAGE_MASTERED]);
                $badgeVariant = 'success';
            } else {
                $badgeText = (string) __('ui.dashboard.action_center.badge_challenge', ['stage' => $targetStage]);
                $badgeVariant = 'primary';
            }

            $reviewUrl = $stage === LearningProgress::STAGE_UNSTARTED
                ? route('groups.intro', ['group' => $progress->group_id], false)
                : route('groups.quiz', ['group' => $progress->group_id], false);

            $dueReviews[] = [
                'group_id' => $progress->group_id,
                'level_id' => $levelId,
                'group_title' => $groupTitle,
                'stage' => $stage,
                'target_stage' => $targetStage,
                'badge_text' => $badgeText,
                'badge_variant' => $badgeVariant,
                'last_score' => $progress->last_score,
                'is_failed' => $isFailed,
                'review_url' => $reviewUrl,
                'next_review_at' => $progress->next_review_at,
            ];
        }

        // Sort due reviews: failed first, then next_review_at asc (null treated as earliest)
        usort($dueReviews, function ($a, $b) {
            if ($a['is_failed'] !== $b['is_failed']) {
                return $a['is_failed'] ? -1 : 1;
            }

            $aTime = $a['next_review_at'] !== null ? $a['next_review_at']->timestamp : 0;
            $bTime = $b['next_review_at'] !== null ? $b['next_review_at']->timestamp : 0;

            return $aTime <=> $bTime;
        });

        // Strip helper key from dueReviews
        $cleanDueReviews = array_map(function ($item) {
            unset($item['next_review_at']);

            return $item;
        }, $dueReviews);

        // Find next unstarted group
        $startedGroupIds = $userProgress->pluck('group_id')->all();

        /** @var Group|null $nextGroup */
        $nextGroup = Group::query()
            ->with('level.translation')
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
                'start_url' => route('groups.intro', ['group' => $nextGroup->id], false),
            ];
        }

        return [
            'due_reviews' => $cleanDueReviews,
            'recommended_next_group' => $recommendedNextGroup,
        ];
    }
}
