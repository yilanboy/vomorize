<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
            $unlockedWordCount = $passedGroupCount * 10;
        }

        return Inertia::render('quiz/Index', [
            'passedGroupCount' => $passedGroupCount,
            'unlockedWordCount' => $unlockedWordCount,
        ]);
    }
}
