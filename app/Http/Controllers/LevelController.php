<?php

namespace App\Http\Controllers;

use App\Actions\CalculateGroupStatusCounts;
use App\Actions\GetLevelGroups;
use App\Actions\GetLevelOverview;
use App\Actions\GetLevelSummary;
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
    public function index(Request $request, GetLevelOverview $getLevelOverview): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        return Inertia::render('Levels', [
            'levels' => $getLevelOverview->handle($user),
        ]);
    }

    /**
     * Display the level detail with all unit groups and their learning statuses.
     */
    public function show(
        Request $request,
        Level $level,
        GetLevelGroups $getLevelGroups,
        CalculateGroupStatusCounts $calculateGroupStatusCounts,
        GetLevelSummary $getLevelSummary
    ): Response {
        /** @var User|null $user */
        $user = $request->user();

        $groups = $getLevelGroups->handle($level, $user);
        $counts = $calculateGroupStatusCounts->handle($groups);
        $levelSummary = $getLevelSummary->handle($level, $groups);

        return Inertia::render('levels/Show', [
            'level' => $levelSummary,
            'groups' => $groups,
            'counts' => $counts,
        ]);
    }
}
