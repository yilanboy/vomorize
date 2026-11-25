<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetWelcomeShowcase;
use App\Models\Level;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
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

        [
            'vocabulary' => $vocabulary,
            'showcase' => $showcase,
        ] = $getWelcomeShowcase->handle();

        return Inertia::render('Page', [
            'levels' => $levels,
            'vocabulary' => $vocabulary,
            'showcase' => $showcase,
        ]);
    }
}
