<?php

namespace App\Http\Controllers;

use App\Actions\GetLevelOverview;
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
}
