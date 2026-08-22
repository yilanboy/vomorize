<?php

namespace App\Http\Controllers;

use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\LevelTranslation;
use App\Services\SpacedRepetitionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $levels = Level::query()
            ->with('translations')
            ->withCount('groups')
            ->orderBy('id')
            ->get()
            ->map(function (Level $level) use ($user) {
                $completedCount = 0;
                $readyCount = 0;
                $pendingCount = 0;

                if ($user) {
                    $progressRecords = LearningProgress::where('user_id', $user->id)
                        ->where('level_id', $level->id)
                        ->get();

                    $now = now();
                    foreach ($progressRecords as $progressRecord) {
                        $status = SpacedRepetitionService::deriveStatus(
                            $progressRecord->stage,
                            $progressRecord->last_score,
                            $progressRecord->next_review_at,
                            $now
                        );

                        if ($status === 'completed') {
                            $completedCount++;
                        } elseif ($status === 'ready') {
                            $readyCount++;
                        } elseif (in_array($status, ['penalty', 'locked'])) {
                            $pendingCount++;
                        }
                    }
                }

                return [
                    'id' => $level->id,
                    'translations' => $level->translations->mapWithKeys(fn (LevelTranslation $item) => [
                        $item->locale => [
                            'locale' => $item->locale,
                            'name' => $item->name,
                            'description' => $item->description,
                        ],
                    ]),
                    'total_groups' => $level->groups_count,
                    'completed_groups' => $completedCount,
                    'ready_groups' => $readyCount,
                    'pending_groups' => $pendingCount,
                ];
            });

        return Inertia::render('Home', [
            'levels' => $levels,
        ]);
    }
}
