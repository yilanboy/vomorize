<?php

namespace App\Actions;

use App\Models\Level;
use App\Models\User;

readonly class GetLevelsProgress
{
    /**
     * @return array<int, array{
     *     total_stages: int,
     *     max_stages: int,
     *     mastery_percentage: float|int,
     * }>
     */
    public function handle(User $user): array
    {
        $levels = Level::query()->withCount('groups')->get(['id']);

        $progressByLevel = $user
            ->learningProgress()
            ->selectRaw('level_id, sum(stage) as total_stages')
            ->groupBy('level_id')
            ->pluck('total_stages', 'level_id');

        $result = [];

        foreach ($levels as $level) {
            $totalStages = (int) $progressByLevel->get($level->id, 0);
            $groupsCount = (int) $level->groups_count;
            $maxStages = $groupsCount * 6;
            $masteryPercentage = $maxStages > 0 ? min(100, round(($totalStages / $maxStages) * 100, 2)) : 0;

            $result[$level->id] = [
                'total_stages' => $totalStages,
                'max_stages' => $maxStages,
                'mastery_percentage' => $masteryPercentage,
            ];
        }

        return $result;
    }
}
