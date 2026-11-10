<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordTodayCheckIn;
use App\Actions\SaveGroupQuizProgress;
use App\Http\Requests\SaveGroupProgressRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class GroupProgressController extends Controller
{
    /**
     * Save group quiz progress for the authenticated user.
     */
    public function store(
        SaveGroupProgressRequest $request,
        Group $group,
        SaveGroupQuizProgress $saveGroupQuizProgress,
        RecordTodayCheckIn $recordTodayCheckIn,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $score = (int) $validated['score'];
        $now = now();

        $saveGroupQuizProgress->handle($user, $group, $score, $now);
        $recordTodayCheckIn->handle($user, $now);

        $request->session()->flash('quiz_completed', true);

        return back();
    }
}
