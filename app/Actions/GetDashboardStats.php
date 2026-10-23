<?php

namespace App\Actions;

use App\Models\LearningProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

readonly class GetDashboardStats
{
    public function __construct(
        private GetDashboardKpis $getDashboardKpis,
        private GetActionCenterQueue $getActionCenterQueue,
        private GetSrsStageBreakdown $getSrsStageBreakdown,
        private GetDashboardLevelProgress $getDashboardLevelProgress,
    ) {}

    /**
     * @return array{
     *     is_empty_state: bool,
     *     kpis: array{
     *         streak: array{
     *             current: int,
     *             longest: int,
     *             is_checked_in_today: bool,
     *         },
     *         learning_words: array{
     *             words_count: int,
     *             groups_count: int,
     *         },
     *         mastered_words: array{
     *             words_count: int,
     *             groups_count: int,
     *             percentage: float,
     *         },
     *         due_groups: array{
     *             count: int,
     *             has_due: bool,
     *         },
     *     },
     *     action_center: array{
     *         due_reviews: array<int, array{
     *             group_id: int,
     *             level_id: int,
     *             group_title: string,
     *             words_count: int,
     *             stage: int,
     *             target_stage: int,
     *             badge_text: string,
     *             badge_variant: string,
     *             last_score: int|null,
     *             status_hint: string,
     *             review_url: string,
     *         }>,
     *         upcoming_reviews: array<int, array{
     *             group_id: int,
     *             level_id: int,
     *             group_title: string,
     *             stage_label: string,
     *             next_review_at: string,
     *             cooldown_remaining_seconds: int,
     *         }>,
     *         recommended_next_group: array{
     *             group_id: int,
     *             level_id: int,
     *             group_sequence: int,
     *             group_title: string,
     *             words_count: int,
     *             start_url: string,
     *         }|null,
     *     },
     *     srs_distribution: array{
     *         total_contacted_groups: int,
     *         total_contacted_words: int,
     *         has_data: bool,
     *         stages: array<int, array{
     *             key: string,
     *             label: string,
     *             groups_count: int,
     *             words_count: int,
     *             percentage: float,
     *             color_class: string,
     *             description: string,
     *             is_alert: bool,
     *         }>,
     *     },
     *     level_progress: array{
     *         levels: array<int, array{
     *             id: int,
     *             name: string,
     *             subtitle: string,
     *             description: string,
     *             mastery_percentage: float|int,
     *             mastered_groups_count: int,
     *             learning_groups_count: int,
     *             total_groups_count: int,
     *             total_words_count: int,
     *             url: string,
     *         }>,
     *         overall: array{
     *             mastered_groups_count: int,
     *             total_groups_count: int,
     *             mastered_words_count: int,
     *             total_words_count: int,
     *             mastery_percentage: float|int,
     *         },
     *     },
     * }
     */
    public function handle(User $user): array
    {
        $now = now();

        /** @var Collection<int, LearningProgress> $userProgress */
        $userProgress = $user->learningProgress()
            ->with(['group.level'])
            ->get();

        return [
            'is_empty_state' => $userProgress->isEmpty(),
            'kpis' => $this->getDashboardKpis->handle($user, $userProgress, $now),
            'action_center' => $this->getActionCenterQueue->handle($user, $userProgress, $now),
            'srs_distribution' => $this->getSrsStageBreakdown->handle($userProgress),
            'level_progress' => $this->getDashboardLevelProgress->handle($user, $userProgress),
        ];
    }
}
