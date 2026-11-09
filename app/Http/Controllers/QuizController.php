<?php

namespace App\Http\Controllers;

use App\Actions\GenerateCustomQuizSession;
use App\Actions\RecordTodayCheckIn;
use App\Http\Requests\CustomQuizRequest;
use App\Models\LearningProgress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Store custom quiz configuration in session and redirect to the quiz room.
     */
    public function start(
        CustomQuizRequest $request,
        GenerateCustomQuizSession $generateCustomQuizSession,
    ): RedirectResponse {
        /** @var User|null $user */
        $user = $request->user();
        $questionCount = (int) $request->validated('question_count');
        /** @var array<int, int> $passedGroupIds */
        $passedGroupIds = array_map('intval', (array) $request->validated('passed_group_ids', []));

        $availableGroupIds = $generateCustomQuizSession->resolveAvailableGroupIds($user, $passedGroupIds);

        if (count($availableGroupIds) * 10 < $questionCount || $questionCount <= 0) {
            return to_route('quiz.index');
        }

        $request->session()->put('custom_quiz_config', [
            'question_count' => $questionCount,
            'passed_group_ids' => $availableGroupIds,
        ]);

        return to_route('quiz.play');
    }

    /**
     * Display the custom quiz room using session configuration.
     */
    public function play(
        Request $request,
        GenerateCustomQuizSession $generateCustomQuizSession,
    ): Response|RedirectResponse {
        /** @var array{question_count?: int, passed_group_ids?: array<int, int>}|null $config */
        $config = $request->session()->get('custom_quiz_config');

        if (! is_array($config) || ! isset($config['question_count'])) {
            return to_route('quiz.index');
        }

        /** @var User|null $user */
        $user = $request->user();
        $questionCount = (int) $config['question_count'];
        /** @var array<int, int> $passedGroupIds */
        $passedGroupIds = array_map('intval', (array) ($config['passed_group_ids'] ?? []));

        $availableGroupIds = $generateCustomQuizSession->resolveAvailableGroupIds($user, $passedGroupIds);

        if (count($availableGroupIds) * 10 < $questionCount || $questionCount <= 0) {
            return to_route('quiz.index');
        }

        return Inertia::render('quiz/Show', [
            'session' => fn () => $generateCustomQuizSession->handle($questionCount, $user, $availableGroupIds),
            'questionCount' => $questionCount,
        ]);
    }

    /**
     * Complete custom quiz and update today's streak check-in.
     */
    public function complete(Request $request, RecordTodayCheckIn $recordTodayCheckIn): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user instanceof User) {
            $recordTodayCheckIn->handle($user);
        }

        return back();
    }
}
