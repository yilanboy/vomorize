<?php

namespace App\Actions;

use App\Models\LearningProgress;
use Illuminate\Database\Eloquent\Collection;

readonly class GetSrsStageBreakdown
{
    /**
     * @param  Collection<int, LearningProgress>  $userProgress
     * @return array{
     *     total_contacted_groups: int,
     *     total_contacted_words: int,
     *     has_data: bool,
     *     stages: array<int, array{
     *         key: string,
     *         label: string,
     *         groups_count: int,
     *         words_count: int,
     *         percentage: float,
     *         color_class: string,
     *         description: string,
     *         is_alert: bool,
     *     }>,
     * }
     */
    public function handle(Collection $userProgress): array
    {
        $stage0 = 0;
        $stage12 = 0;
        $stage34 = 0;
        $stage5 = 0;
        $stage6 = 0;

        foreach ($userProgress as $progress) {
            $stage = $progress->stage;
            $isFailed = $progress->last_score < 80;

            if ($stage === 0 || ($stage < 6 && $isFailed)) {
                $stage0++;
            } elseif ($stage >= 1 && $stage <= 2) {
                $stage12++;
            } elseif ($stage >= 3 && $stage <= 4) {
                $stage34++;
            } elseif ($stage === 5) {
                $stage5++;
            } elseif ($stage >= 6) {
                $stage6++;
            }
        }

        $totalGroups = $userProgress->count();
        $hasData = $totalGroups > 0;

        $calcPct = fn (int $count) => $hasData ? round(($count / $totalGroups) * 100, 1) : 0.0;

        $stages = [
            [
                'key' => 'stage_0',
                'label' => (string) __('ui.dashboard.srs.stages.stage_0.label'),
                'groups_count' => $stage0,
                'words_count' => $stage0 * 10,
                'percentage' => $calcPct($stage0),
                'color_class' => 'bg-red-500',
                'description' => (string) __('ui.dashboard.srs.stages.stage_0.description'),
                'is_alert' => true,
            ],
            [
                'key' => 'stage_1_2',
                'label' => (string) __('ui.dashboard.srs.stages.stage_1_2.label'),
                'groups_count' => $stage12,
                'words_count' => $stage12 * 10,
                'percentage' => $calcPct($stage12),
                'color_class' => 'bg-sky-500',
                'description' => (string) __('ui.dashboard.srs.stages.stage_1_2.description'),
                'is_alert' => false,
            ],
            [
                'key' => 'stage_3_4',
                'label' => (string) __('ui.dashboard.srs.stages.stage_3_4.label'),
                'groups_count' => $stage34,
                'words_count' => $stage34 * 10,
                'percentage' => $calcPct($stage34),
                'color_class' => 'bg-blue-600',
                'description' => (string) __('ui.dashboard.srs.stages.stage_3_4.description'),
                'is_alert' => false,
            ],
            [
                'key' => 'stage_5',
                'label' => (string) __('ui.dashboard.srs.stages.stage_5.label'),
                'groups_count' => $stage5,
                'words_count' => $stage5 * 10,
                'percentage' => $calcPct($stage5),
                'color_class' => 'bg-teal-600',
                'description' => (string) __('ui.dashboard.srs.stages.stage_5.description'),
                'is_alert' => false,
            ],
            [
                'key' => 'stage_6',
                'label' => (string) __('ui.dashboard.srs.stages.stage_6.label'),
                'groups_count' => $stage6,
                'words_count' => $stage6 * 10,
                'percentage' => $calcPct($stage6),
                'color_class' => 'bg-emerald-600',
                'description' => (string) __('ui.dashboard.srs.stages.stage_6.description'),
                'is_alert' => false,
            ],
        ];

        return [
            'total_contacted_groups' => $totalGroups,
            'total_contacted_words' => $totalGroups * 10,
            'has_data' => $hasData,
            'stages' => $stages,
        ];
    }
}
