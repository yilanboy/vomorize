<?php

namespace App\Actions;

use App\Models\Level;
use Illuminate\Support\Collection;

class GetLevelSummary
{
    /**
     * @param  Collection<int, array{stage: int, ...}>  $groups
     * @return array{
     *     id: int,
     *     name: string,
     *     description: string,
     *     vocabularies_count: int,
     *     groups_count: int,
     *     total_stages: int,
     *     max_stages: int,
     *     mastery_percentage: int
     * }
     */
    public function handle(Level $level, Collection $groups): array
    {
        $level->loadCount('vocabularies');

        $groupsCount = $groups->count();
        $totalStages = (int) $groups->sum('stage');
        $maxStages = $groupsCount * 6;
        $masteryPercentage = $maxStages > 0
            ? (int) min(100, round(($totalStages / $maxStages) * 100))
            : 0;

        return [
            'id' => $level->id,
            'name' => $level->name,
            'description' => $level->description,
            'vocabularies_count' => (int) ($level->vocabularies_count ?? ($groupsCount * 10)),
            'groups_count' => $groupsCount,
            'total_stages' => $totalStages,
            'max_stages' => $maxStages,
            'mastery_percentage' => $masteryPercentage,
        ];
    }
}
