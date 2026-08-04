<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Services\SpacedRepetitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroupProgressController extends Controller
{
    public function store(Request $request, Group $group): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'phase' => ['required', 'string', 'in:introduce,quiz'],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $score = $validated['score'];
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => true,
                'passed' => $score >= 90,
                'progress' => null,
            ]);
        }

        if ($validated['phase'] === 'introduce' && $score < 90) {
            return response()->json([
                'success' => true,
                'passed' => false,
                'progress' => null,
            ]);
        }

        $progress = LearningProgress::firstOrNew([
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);

        $currentStage = $progress->exists ? $progress->stage : 0;
        $nextState = SpacedRepetitionService::calculateNextState($currentStage, $score);

        $progress->stage = $nextState['stage'];
        $progress->last_score = $nextState['last_score'];
        $progress->last_reviewed_at = $nextState['last_reviewed_at'];
        $progress->next_review_at = $nextState['next_review_at'];
        $progress->save();

        $status = SpacedRepetitionService::deriveStatus(
            $progress->stage,
            $progress->last_score,
            $progress->next_review_at
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'passed' => $nextState['passed'],
                'progress' => [
                    'stage' => $progress->stage,
                    'last_score' => $progress->last_score,
                    'last_reviewed_at' => $progress->last_reviewed_at ? $progress->last_reviewed_at->toIso8601String() : null,
                    'next_review_at' => $progress->next_review_at ? $progress->next_review_at->toIso8601String() : null,
                    'status' => $status,
                ],
            ]);
        }

        return back();
    }
}
