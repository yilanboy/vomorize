<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateStage1Session;
use App\Actions\GetGroupDetail;
use App\Actions\GetGroupProgress;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupIntroController extends Controller
{
    /**
     * Display the stage 1 vocabulary intro and quiz session.
     */
    public function show(
        Request $request,
        Group $group,
        GetGroupDetail $getGroupDetail,
        GenerateStage1Session $generateStage1Session,
        GetGroupProgress $getGroupProgress,
    ): Response|RedirectResponse {
        /** @var User|null $user */
        $user = $request->user();

        if ($user instanceof User && ! $request->session()->get('quiz_completed')) {
            $progress = $getGroupProgress->handle($group, $user);
            if ($progress['stage'] >= 1) {
                return redirect()->route('groups.show', $group);
            }
        }

        return Inertia::render('groups/intro/Page', [
            'group' => $getGroupDetail->handle($group),
            'session' => fn () => $generateStage1Session->handle($group),
            'progress' => $user instanceof User ? $getGroupProgress->handle($group, $user) : null,
        ]);
    }
}
