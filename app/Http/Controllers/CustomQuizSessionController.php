<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateCustomQuizSession;
use App\Actions\RecordTodayCheckIn;
use App\Http\Requests\CustomQuizRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomQuizSessionController extends Controller
{
    /**
     * Store custom quiz configuration in session and redirect to the quiz room.
     */
    public function store(
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

        $quizMode = (string) $request->validated('quiz_mode', 'standard');

        $request->session()->put('custom_quiz_config', [
            'question_count' => $questionCount,
            'quiz_mode' => $quizMode,
            'passed_group_ids' => $availableGroupIds,
        ]);

        return to_route('quiz.play');
    }

    /**
     * Complete custom quiz and update today's streak check-in.
     */
    public function destroy(Request $request, RecordTodayCheckIn $recordTodayCheckIn): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user instanceof User) {
            $recordTodayCheckIn->handle($user);
        }

        return back();
    }
}
