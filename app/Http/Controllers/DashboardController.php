<?php

namespace App\Http\Controllers;

use App\Actions\GetDashboardStats;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the user's learning dashboard.
     */
    public function index(Request $request, GetDashboardStats $getDashboardStats): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'stats' => $getDashboardStats->handle($user),
        ]);
    }
}
