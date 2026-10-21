<?php

namespace App\Http\Controllers;

use App\Actions\GetGroupDetail;
use App\Actions\GetGroupProgress;
use App\Actions\GetGroupVocabularies;
use App\Models\Group;
use App\Models\User;
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
    ): Response {
        /** @var User|null $user */
        $user = $request->user();

        return Inertia::render('groups/Show', [
            'group' => $getGroupDetail->handle($group),
            'vocabularies' => $getGroupVocabularies->handle($group),
            'progress' => $user instanceof User ? $getGroupProgress->handle($group, $user) : null,
        ]);
    }
}
