<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Vocabulary;
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

        // Get a random vocabulary and show it on welcome page
        $vocabulary = Vocabulary::query()
            ->with('translation:vocabulary_id,definition,example_translation')
            ->inRandomOrder()
            ->first(['id', 'word', 'part_of_speech', 'pronunciation', 'example_sentence']);

        return Inertia::render('Page', [
            'levels' => $levels,
            'vocabulary' => $vocabulary,
        ]);
    }
}
