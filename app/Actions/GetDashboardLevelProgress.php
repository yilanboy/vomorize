<?php

namespace App\Actions;

use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

readonly class GetDashboardLevelProgress
{
    /**
     * @param  Collection<int, LearningProgress>  $userProgress
     * @return array{
     *     levels: array<int, array{
     *         id: int,
     *         name: string,
     *         subtitle: string,
     *         description: string,
     *         mastery_percentage: float|int,
     *         mastered_groups_count: int,
     *         learning_groups_count: int,
     *         total_groups_count: int,
     *         total_words_count: int,
     *         url: string,
     *     }>,
     *     overall: array{
     *         mastered_groups_count: int,
     *         total_groups_count: int,
     *         mastered_words_count: int,
     *         total_words_count: int,
     *         mastery_percentage: float|int,
     *     },
     * }
     */
    public function handle(User $user, Collection $userProgress): array
    {
        $levels = Level::query()
            ->withCount(['groups', 'vocabularies'])
            ->orderBy('id')
            ->get();

        $subtitles = (array) __('ui.dashboard.levels.subtitles');

        $levelItems = [];
        $totalMasteredGroups = 0;
        $totalGroupsAll = 0;
        $totalStagesAll = 0;

        foreach ($levels as $level) {
            $levelId = (int) $level->id;
            $groupsInLevel = (int) ($level->groups_count ?? 100);
            $vocabCount = (int) ($level->vocabularies_count ?? 0);
            $wordsInLevel = $vocabCount > 0 ? $vocabCount : ($groupsInLevel * 10);

            $progressForLevel = $userProgress->where('level_id', $levelId);

            $masteredInLevel = 0;
            $learningInLevel = 0;
            $totalStagesInLevel = 0;

            foreach ($progressForLevel as $p) {
                $st = (int) $p->stage;
                $totalStagesInLevel += min(6, max(0, $st));

                if ($st >= 6) {
                    $masteredInLevel++;
                } elseif ($st >= 1) {
                    $learningInLevel++;
                }
            }

            $maxStagesInLevel = $groupsInLevel * 6;
            $masteryPct = $maxStagesInLevel > 0
                ? min(100, round(($totalStagesInLevel / $maxStagesInLevel) * 100, 2))
                : 0;

            $totalMasteredGroups += $masteredInLevel;
            $totalGroupsAll += $groupsInLevel;
            $totalStagesAll += $totalStagesInLevel;

            $levelItems[] = [
                'id' => $levelId,
                'name' => $level->name,
                'subtitle' => $subtitles[$levelId] ?? $level->name,
                'description' => $level->description ?? '',
                'mastery_percentage' => $masteryPct,
                'mastered_groups_count' => $masteredInLevel,
                'learning_groups_count' => $learningInLevel,
                'total_groups_count' => $groupsInLevel,
                'total_words_count' => $wordsInLevel,
                'url' => "/levels/{$levelId}",
            ];
        }

        $maxStagesAll = $totalGroupsAll * 6;
        $overallMasteryPct = $maxStagesAll > 0
            ? min(100, round(($totalStagesAll / $maxStagesAll) * 100, 1))
            : 0;

        return [
            'levels' => $levelItems,
            'overall' => [
                'mastered_groups_count' => $totalMasteredGroups,
                'total_groups_count' => $totalGroupsAll,
                'mastered_words_count' => $totalMasteredGroups * 10,
                'total_words_count' => $totalGroupsAll * 10,
                'mastery_percentage' => $overallMasteryPct,
            ],
        ];
    }
}
