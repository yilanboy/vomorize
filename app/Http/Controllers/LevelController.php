<?php

namespace App\Http\Controllers;

use App\Actions\GetLevelDetail;
use App\Actions\GetLevelGroups;
use App\Actions\GetLevelProgress;
use App\Actions\GetLevels;
use App\Actions\GetLevelsProgress;
use App\Models\Level;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LevelController extends Controller
{
    /**
     * Display the vocabulary levels overview page.
     */
    public function index(Request $request, GetLevels $getLevels, GetLevelsProgress $getLevelsProgress): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        return Inertia::render('Levels', [
            'levels' => $getLevels->handle(),
            'progress' => $user instanceof User ? $getLevelsProgress->handle($user) : null,
        ]);
    }

    /**
     * Display the level detail with all unit groups and their learning statuses.
     */
    public function show(
        Request $request,
        Level $level,
        GetLevelDetail $getLevelDetail,
        GetLevelGroups $getLevelGroups,
        GetLevelProgress $getLevelProgress,
    ): Response {
        /** @var User|null $user */
        $user = $request->user();

        return Inertia::render('levels/Show', [
            'level' => $getLevelDetail->handle($level),
            'groups' => $getLevelGroups->handle($level),
            'progress' => $user instanceof User ? $getLevelProgress->handle($level, $user) : null,
        ]);
    }
}
