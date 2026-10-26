<?php

namespace App\Http\Controllers;

use App\Actions\SyncLearningProgress;
use App\Http\Requests\SyncLearningProgressRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProgressSyncController extends Controller
{
    /**
     * Sync guest learning progress from local storage into the authenticated user's account.
     */
    public function store(SyncLearningProgressRequest $request, SyncLearningProgress $syncLearningProgress): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<int, array{group_id: int, level_id?: int, stage: int, last_score: int, last_reviewed_at: string, next_review_at?: string|null}> $localProgress */
        $localProgress = $request->validated('local_progress');

        $syncedCount = $syncLearningProgress->handle($user, $localProgress);

        if ($syncedCount > 0) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => (string) __('ui.sync.success', ['count' => $syncedCount]),
            ]);
        }

        return back();
    }
}
