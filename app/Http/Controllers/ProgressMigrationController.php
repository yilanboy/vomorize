<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\LearningProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProgressMigrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'guest_progress' => ['required', 'array'],
            'guest_progress.*.level_id' => ['required', 'integer', 'exists:levels,id'],
            'guest_progress.*.group_id' => ['required', 'integer', 'exists:groups,id'],
            'guest_progress.*.stage' => ['required', 'integer', 'min:0', 'max:6'],
            'guest_progress.*.last_score' => ['required', 'integer', 'min:0', 'max:100'],
            'guest_progress.*.last_reviewed_at' => ['required', 'string'],
            'guest_progress.*.next_review_at' => ['nullable', 'string'],
        ]);

        $migratedCount = 0;

        foreach ($validated['guest_progress'] as $item) {
            $groupId = $item['group_id'];
            $levelId = $item['level_id'] ?? Group::where('id', $groupId)->value('level_id');
            $guestStage = (int) $item['stage'];
            $guestScore = (int) $item['last_score'];
            $guestLastReviewed = Carbon::parse($item['last_reviewed_at']);
            $guestNextReview = $item['next_review_at'] ? Carbon::parse($item['next_review_at']) : null;

            $existing = LearningProgress::where('user_id', $user->id)
                ->where('group_id', $groupId)
                ->first();

            if (! $existing) {
                LearningProgress::create([
                    'user_id' => $user->id,
                    'level_id' => $levelId,
                    'group_id' => $groupId,
                    'stage' => $guestStage,
                    'last_score' => $guestScore,
                    'last_reviewed_at' => $guestLastReviewed,
                    'next_review_at' => $guestNextReview,
                ]);
                $migratedCount++;

                continue;
            }

            // Conflict resolution: keep more advanced progress
            $useGuest = false;

            if ($guestStage > $existing->stage) {
                $useGuest = true;
            } elseif ($guestStage === $existing->stage) {
                if ($guestScore > $existing->last_score) {
                    $useGuest = true;
                } elseif ($guestScore === $existing->last_score) {
                    if ($guestLastReviewed->isAfter($existing->last_reviewed_at)) {
                        $useGuest = true;
                    }
                }
            }

            if ($useGuest) {
                $existing->update([
                    'stage' => $guestStage,
                    'last_score' => $guestScore,
                    'last_reviewed_at' => $guestLastReviewed,
                    'next_review_at' => $guestNextReview,
                ]);
                $migratedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Progress migrated successfully.',
            'migrated_count' => $migratedCount,
        ]);
    }
}
