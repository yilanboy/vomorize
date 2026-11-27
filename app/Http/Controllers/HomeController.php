<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetWelcomeShowcase;
use App\Http\Resources\LevelResource;
use App\Models\Level;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    /**
     * Display the welcome page with all vocabulary levels.
     */
    public function index(GetWelcomeShowcase $getWelcomeShowcase): Response
    {
        $levels = Level::query()
            ->with('translation')
            ->withCount(['vocabularies', 'groups'])
            ->orderBy('id', 'asc')
            ->limit(7)
            ->get(['id']);

        $showcase = $getWelcomeShowcase->handle();

        return Inertia::render('Page', [
            'levels' => LevelResource::collection($levels),
            'showcase' => $showcase,
        ]);
    }
}
