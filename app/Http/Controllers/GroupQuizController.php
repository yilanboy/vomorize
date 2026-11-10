<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateReviewQuizSession;
use App\Actions\GetGroupDetail;
use App\Actions\GetGroupProgress;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupQuizController extends Controller
{
    /**
     * Display the stage 2~6 vocabulary review quiz session.
     */
    public function show(
        Request $request,
        Group $group,
        GetGroupDetail $getGroupDetail,
        GenerateReviewQuizSession $generateReviewQuizSession,
        GetGroupProgress $getGroupProgress,
    ): Response|RedirectResponse {
        /** @var User|null $user */
        $user = $request->user();

        if ($user instanceof User && ! $request->session()->get('quiz_completed')) {
            $progress = $getGroupProgress->handle($group, $user);
            if ($progress['stage'] < 1) {
                return redirect()->route('groups.intro', $group);
            }

            if ($progress['status'] === 'cooldown') {
                return redirect()->route('groups.show', $group);
            }
        }

        return Inertia::render('groups/Quiz', [
            'group' => $getGroupDetail->handle($group),
            'session' => fn () => $generateReviewQuizSession->handle($group),
            'progress' => $user instanceof User ? $getGroupProgress->handle($group, $user) : null,
        ]);
    }
}
