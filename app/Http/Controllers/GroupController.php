<?php

namespace App\Http\Controllers;

use App\Actions\GenerateStage1Session;
use App\Actions\GetGroupDetail;
use App\Actions\GetGroupProgress;
use App\Actions\GetGroupVocabularies;
use App\Actions\SaveGroupQuizProgress;
use App\Http\Requests\SaveGroupProgressRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    /**
     * Display the group vocabulary list page.
     */
    public function show(
        Request $request,
        Group $group,
        GetGroupDetail $getGroupDetail,
        GetGroupVocabularies $getGroupVocabularies,
        GetGroupProgress $getGroupProgress,
    ): Response|RedirectResponse {
        /** @var User|null $user */
        $user = $request->user();

        if ($user instanceof User) {
            $progress = $getGroupProgress->handle($group, $user);
            if ($progress['stage'] < 1) {
                return redirect()->route('groups.intro', $group);
            }
        }

        return Inertia::render('groups/Show', [
            'group' => $getGroupDetail->handle($group),
            'vocabularies' => $getGroupVocabularies->handle($group),
            'progress' => $user instanceof User ? $getGroupProgress->handle($group, $user) : null,
        ]);
    }

    /**
     * Display the stage 1 vocabulary intro and quiz session.
     */
    public function intro(
        Request $request,
        Group $group,
        GetGroupDetail $getGroupDetail,
        GenerateStage1Session $generateStage1Session,
        GetGroupProgress $getGroupProgress,
    ): Response|RedirectResponse {
        /** @var User|null $user */
        $user = $request->user();

        if ($user instanceof User) {
            $progress = $getGroupProgress->handle($group, $user);
            if ($progress['stage'] >= 1) {
                return redirect()->route('groups.show', $group);
            }
        }

        return Inertia::render('groups/Intro', [
            'group' => $getGroupDetail->handle($group),
            'session' => $generateStage1Session->handle($group),
            'progress' => $user instanceof User ? $getGroupProgress->handle($group, $user) : null,
        ]);
    }

    /**
     * Save group quiz progress for the authenticated user.
     */
    public function saveProgress(
        SaveGroupProgressRequest $request,
        Group $group,
        SaveGroupQuizProgress $saveGroupQuizProgress,
        GetGroupProgress $getGroupProgress,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $score = (int) $validated['score'];

        $saveGroupQuizProgress->handle($user, $group, $score);
        $progress = $getGroupProgress->handle($group, $user);

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }
}
