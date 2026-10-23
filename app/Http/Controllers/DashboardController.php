<?php

namespace App\Http\Controllers;

use App\Actions\GetActionCenterQueue;
use App\Actions\GetDashboardKpis;
use App\Actions\GetDashboardLevelProgress;
use App\Actions\GetSrsStageBreakdown;
use App\Models\LearningProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the user's learning dashboard.
     */
    public function index(
        Request $request,
        GetDashboardKpis $getDashboardKpis,
        GetActionCenterQueue $getActionCenterQueue,
        GetSrsStageBreakdown $getSrsStageBreakdown,
        GetDashboardLevelProgress $getDashboardLevelProgress,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $now = now();

        /** @var Collection<int, LearningProgress> $userProgress */
        $userProgress = $user->learningProgress()
            ->with(['group.level'])
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'is_empty_state' => $userProgress->isEmpty(),
                'kpis' => $getDashboardKpis->handle($user, $userProgress, $now),
                'action_center' => $getActionCenterQueue->handle($user, $userProgress, $now),
                'srs_distribution' => $getSrsStageBreakdown->handle($userProgress),
                'level_progress' => $getDashboardLevelProgress->handle($user, $userProgress),
            ],
        ]);
    }
}
