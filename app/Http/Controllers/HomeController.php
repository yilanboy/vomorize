<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildSentenceClozeQuestion;
use App\Actions\BuildSpellingQuestion;
use App\Http\Resources\LevelResource;
use App\Http\Resources\VocabularyResource;
use App\Models\Level;
use App\Models\Vocabulary;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    /**
     * Display the welcome page with vocabulary levels and an interactive sample vocabulary.
     */
    public function index(
        BuildSentenceClozeQuestion $buildSentenceClozeQuestion,
        BuildSpellingQuestion $buildSpellingQuestion,
    ): Response {
        $levels = Level::query()
            ->with('translation')
            ->withCount(['vocabularies', 'groups'])
            ->orderBy('id', 'asc')
            ->limit(7)
            ->get(['id']);

        $vocabulary = Vocabulary::query()
            ->whereHas('sentenceClozeQuestion')
            ->with(['translation', 'sentenceClozeQuestion'])
            ->inRandomOrder()
            ->firstOrFail();

        return Inertia::render('Page', [
            'levels' => LevelResource::collection($levels),
            'vocabulary' => new VocabularyResource($vocabulary),
            'cloze_question' => $buildSentenceClozeQuestion->handle($vocabulary),
            'spelling_question' => $buildSpellingQuestion->handle($vocabulary),
        ]);
    }
}
