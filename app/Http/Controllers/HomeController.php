<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Level;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the welcome page with all vocabulary levels.
     */
    public function index(): Response
    {
        $levels = Level::query()
            ->with('translation')
            ->withCount(['vocabularies', 'groups'])
            ->orderBy('id', 'asc')
            ->limit(7)
            ->get(['id']);

        return Inertia::render('Page', [
            'levels' => $levels,
        ]);
    }
}
