<?php

namespace App\Actions;

use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Collection;

class GetLevelOverview
{
    /**
     * @return Collection<int, array{
     *     id: int<0, max>,
     *     name: string,
     *     description: string,
     *     vocabularies_count: int<0, max>,
     *     groups_count: int<0, max>,
     *     total_stages: int,
     *     max_stages: int<0, max>,
     *     mastery_percentage: int
     * }>
     */
    public function handle(?User $user): Collection
    {
        $levels = Level::query()
            ->withCount(['vocabularies', 'groups'])
            ->orderBy('id', 'asc')
            ->get(['id', 'name', 'description']);

        $progressByLevel = $user instanceof User
            ? $user->learningProgress()
                ->selectRaw('level_id, sum(stage) as total_stages')
                ->groupBy('level_id')
                ->pluck('total_stages', 'level_id')
            : collect();

        return $levels->map(function (Level $level) use ($progressByLevel): array {
            $totalStages = (int) $progressByLevel->get($level->id, 0);
            $groupsCount = (int) $level->groups_count;
            $maxStages = $groupsCount * 6;
            $masteryPercentage = $maxStages > 0
                ? (int) min(100, round(($totalStages / $maxStages) * 100))
                : 0;

            return [
                'id' => $level->id,
                'name' => $level->name,
                'description' => $level->description,
                'vocabularies_count' => (int) ($level->vocabularies_count ?? 1000),
                'groups_count' => $groupsCount,
                'total_stages' => $totalStages,
                'max_stages' => $maxStages,
                'mastery_percentage' => $masteryPercentage,
            ];
        });
    }
}
