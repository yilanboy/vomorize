<?php

namespace App\Http\Controllers;

use App\Actions\GetLevelOverview;
use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class LevelController extends Controller
{
    /**
     * Display the vocabulary levels overview page.
     */
    public function index(Request $request, GetLevelOverview $getLevelOverview): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        return Inertia::render('Levels', [
            'levels' => $getLevelOverview->handle($user),
        ]);
    }

    /**
     * Display the level detail with all unit groups and their learning statuses.
     */
    public function show(Request $request, Level $level): Response
    {
        $level->loadCount(['vocabularies', 'groups']);

        $groups = $level->groups()
            ->orderBy('sequence', 'asc')
            ->get(['id', 'level_id', 'sequence']);

        /** @var User|null $user */
        $user = $request->user();

        /** @var Collection<int, LearningProgress> $userProgress */
        $userProgress = $user instanceof User
            ? $user->learningProgress()
                ->where('level_id', $level->id)
                ->get()
                ->keyBy('group_id')
            : collect();

        $now = now();
        $totalStages = 0;
        $readyCount = 0;
        $cooldownCount = 0;
        $completedCount = 0;
        $notStartedCount = 0;

        // Determine if any group is due for review
        $hasDueReviews = false;
        if ($user instanceof User) {
            foreach ($groups as $group) {
                $progress = $userProgress->get($group->id);
                if ($progress instanceof LearningProgress && $progress->stage > 0 && $progress->stage < 6) {
                    if ($progress->next_review_at === null || $progress->next_review_at->lte($now)) {
                        $hasDueReviews = true;
                        break;
                    }
                }
            }
        }

        $firstUnstartedFound = false;

        $groupsData = $groups->map(function (Group $group) use (
            $user,
            $userProgress,
            $now,
            $hasDueReviews,
            &$totalStages,
            &$readyCount,
            &$cooldownCount,
            &$completedCount,
            &$notStartedCount,
            &$firstUnstartedFound
        ): array {
            /** @var LearningProgress|null $progress */
            $progress = $userProgress->get($group->id);
            $stage = (int) ($progress?->stage ?? 0);
            $totalStages += $stage;

            $status = 'not_started';
            $cooldownRemainingSeconds = null;

            if ($user instanceof User) {
                if ($stage >= 6) {
                    $status = 'completed';
                    $completedCount++;
                } elseif ($stage > 0) {
                    if ($progress?->next_review_at && $progress->next_review_at->isFuture()) {
                        $status = 'cooldown';
                        $cooldownRemainingSeconds = max(0, $now->diffInSeconds($progress->next_review_at, false));
                        $cooldownCount++;
                    } else {
                        $status = 'ready';
                        $readyCount++;
                    }
                } else {
                    // No progress yet (stage == 0)
                    // If no review is due, designate the first unstarted group as 'ready' to guide user forward
                    if (! $hasDueReviews && ! $firstUnstartedFound) {
                        $status = 'ready';
                        $readyCount++;
                        $firstUnstartedFound = true;
                    } else {
                        $status = 'not_started';
                        $notStartedCount++;
                    }
                }
            } else {
                // Guest mode: Group 1 is ready, others not started
                if (! $firstUnstartedFound) {
                    $status = 'ready';
                    $readyCount++;
                    $firstUnstartedFound = true;
                } else {
                    $status = 'not_started';
                    $notStartedCount++;
                }
            }

            return [
                'id' => $group->id,
                'sequence' => $group->sequence,
                'status' => $status,
                'stage' => $stage,
                'next_review_at' => $progress?->next_review_at?->toIso8601String(),
                'cooldown_remaining_seconds' => $cooldownRemainingSeconds,
                'vocabularies_count' => 10,
            ];
        });

        $groupsCount = $groups->count();
        $maxStages = $groupsCount * 6;
        $masteryPercentage = $maxStages > 0
            ? (int) min(100, round(($totalStages / $maxStages) * 100))
            : 0;

        return Inertia::render('levels/Show', [
            'level' => [
                'id' => $level->id,
                'name' => $level->name,
                'description' => $level->description,
                'vocabularies_count' => (int) ($level->vocabularies_count ?? ($groupsCount * 10)),
                'groups_count' => $groupsCount,
                'total_stages' => $totalStages,
                'max_stages' => $maxStages,
                'mastery_percentage' => $masteryPercentage,
            ],
            'groups' => $groupsData,
            'counts' => [
                'all' => $groupsCount,
                'ready' => $readyCount,
                'cooldown' => $cooldownCount,
                'not_started' => $notStartedCount,
                'completed' => $completedCount,
            ],
        ]);
    }
}
