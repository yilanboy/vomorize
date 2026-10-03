<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LevelController extends Controller
{
    /**
     * Display the vocabulary levels overview page.
     */
    public function index(Request $request): Response
    {
        $levels = Level::query()
            ->withCount(['vocabularies', 'groups'])
            ->orderBy('id', 'asc')
            ->get(['id', 'name', 'description']);

        /** @var User|null $user */
        $user = $request->user();

        $progressByLevel = $user instanceof User
            ? $user->learningProgress()
                ->selectRaw('level_id, sum(stage) as total_stages')
                ->groupBy('level_id')
                ->pluck('total_stages', 'level_id')
            : collect();

        $levelsData = $levels->map(function (Level $level) use ($progressByLevel): array {
            $totalStages = (int) $progressByLevel->get($level->id, 0);
            $groupsCount = (int) ($level->groups_count ?? 100);
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

        return Inertia::render('Levels', [
            'levels' => $levelsData,
        ]);
    }
}
