<?php

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
            ->withCount(['vocabularies', 'groups'])
            ->orderBy('id', 'asc')
            ->limit(7)
            ->get(['id', 'name', 'description']);

        return Inertia::render('Welcome', [
            'levels' => $levels,
        ]);
    }
}
