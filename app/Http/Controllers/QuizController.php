<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateCustomQuizSession;
use App\Enums\QuizMode;
use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    /**
     * Display the custom quiz setup page.
     */
    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        $passedGroupCount = null;
        $unlockedWordCount = null;

        if ($user instanceof User) {
            $passedGroupCount = LearningProgress::query()
                ->where('user_id', $user->id)
                ->where('stage', '>=', 1)
                ->count();

            $unlockedWordCount = $passedGroupCount * Group::VOCABULARY_PER_GROUP;
        }

        return Inertia::render('quiz/index/Page', [
            'passedGroupCount' => $passedGroupCount,
            'unlockedWordCount' => $unlockedWordCount,
            'questionCountOptions' => GenerateCustomQuizSession::QUESTION_COUNT_OPTIONS,
            'defaultQuestionCount' => GenerateCustomQuizSession::DEFAULT_QUESTION_COUNT,
            'availableQuizModes' => QuizMode::availableValues(),
            'defaultQuizMode' => QuizMode::defaultMode()->value,
        ]);
    }
}
