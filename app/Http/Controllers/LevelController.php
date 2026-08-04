<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Services\SpacedRepetitionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LevelController extends Controller
{
    public function show(Request $request, Level $level): Response
    {
        $user = $request->user();
        $now = now();

        $progressMap = [];
        if ($user) {
            $records = LearningProgress::where('user_id', $user->id)
                ->whereIn('group_id', Group::where('level_id', $level->id)->pluck('id'))
                ->get();

            foreach ($records as $r) {
                $progressMap[$r->group_id] = $r;
            }
        }

        $groups = Group::where('level_id', $level->id)
            ->orderBy('sequence')
            ->get()
            ->map(function (Group $group) use ($progressMap, $now) {
                $p = $progressMap[$group->id] ?? null;
                $stage = $p ? $p->stage : 0;
                $lastScore = $p ? $p->last_score : null;
                $lastReviewedAt = $p && $p->last_reviewed_at ? $p->last_reviewed_at->toIso8601String() : null;
                $nextReviewAt = $p && $p->next_review_at ? $p->next_review_at->toIso8601String() : null;
                $status = SpacedRepetitionService::deriveStatus(
                    $stage,
                    $lastScore,
                    $p ? $p->next_review_at : null,
                    $now
                );

                return [
                    'id' => $group->id,
                    'sequence' => $group->sequence,
                    'level_id' => $group->level_id,
                    'stage' => $stage,
                    'last_score' => $lastScore,
                    'last_reviewed_at' => $lastReviewedAt,
                    'next_review_at' => $nextReviewAt,
                    'status' => $status,
                ];
            });

        $level->load('translations');

        return Inertia::render('level/Show', [
            'level' => [
                'id' => $level->id,
                'translations' => $level->translations->map(fn ($t) => [
                    'locale' => $t->locale,
                    'name' => $t->name,
                    'description' => $t->description,
                ]),
            ],
            'groups' => $groups,
        ]);
    }
}
