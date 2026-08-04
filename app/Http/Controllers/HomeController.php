<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Services\SpacedRepetitionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $levels = Level::with('translations')->orderBy('id')->get()->map(function (Level $level) use ($request) {
            $totalGroups = 100;
            $completedCount = 0;
            $readyCount = 0;

            if ($request->user()) {
                $groupIds = Group::where('level_id', $level->id)->pluck('id');
                $progressRecords = LearningProgress::where('user_id', $request->user()->id)
                    ->whereIn('group_id', $groupIds)
                    ->get();

                $now = now();
                foreach ($progressRecords as $p) {
                    $status = SpacedRepetitionService::deriveStatus($p->stage, $p->last_score, $p->next_review_at, $now);
                    if ($status === 'completed') {
                        $completedCount++;
                    } elseif ($status === 'ready') {
                        $readyCount++;
                    }
                }
            }

            return [
                'id' => $level->id,
                'translations' => $level->translations->map(fn ($t) => [
                    'locale' => $t->locale,
                    'name' => $t->name,
                    'description' => $t->description,
                ]),
                'total_groups' => $totalGroups,
                'completed_groups' => $completedCount,
                'ready_groups' => $readyCount,
            ];
        });

        return Inertia::render('Home', [
            'levels' => $levels,
        ]);
    }
}
