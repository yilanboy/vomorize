<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateCustomQuizSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizPlayController extends Controller
{
    /**
     * Display the custom quiz room using session configuration.
     */
    public function show(
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
}
