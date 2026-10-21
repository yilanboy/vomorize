<?php

namespace App\Actions;

use App\Enums\GroupStatus;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

readonly class GetLevelProgress
{
    public function __construct(
        private DetermineGroupStatus $determineGroupStatus,
    ) {}

    /**
     * @return array{
     *     groups: array<int, array{
     *         status: string,
     *         stage: int,
     *         next_review_at: string|null,
     *         cooldown_remaining_seconds: int|null,
     *     }>,
     *     counts: array{
     *         all: int,
     *         ready: int,
     *         cooldown: int,
     *         not_started: int,
     *         completed: int,
     *     },
     * }
     */
    public function handle(Level $level, User $user): array
    {
        $groupsCount = $level->groups()->count();

        /** @var Collection<int, LearningProgress> $userProgress */
        $userProgress = $user->learningProgress()
            ->where('level_id', $level->id)
            ->get()
            ->keyBy('group_id');

        $now = now();
        $groupProgressMap = [];
        $ready = 0;
        $cooldown = 0;
        $completed = 0;
        $notStarted = 0;

        foreach ($userProgress as $groupId => $progress) {
            $statusResult = $this->determineGroupStatus->handle($progress, $now);
            $status = $statusResult['status'];

            $groupProgressMap[(int) $groupId] = [
                'status' => $status->value,
                'stage' => $progress->stage ?? 0,
                'next_review_at' => $progress->next_review_at?->toIso8601String(),
                'cooldown_remaining_seconds' => $statusResult['cooldown_remaining_seconds'],
            ];

            match ($status) {
                GroupStatus::Ready => $ready++,
                GroupStatus::Cooldown => $cooldown++,
                GroupStatus::Completed => $completed++,
                GroupStatus::NotStarted => $notStarted++,
            };
        }

        $unrecordedGroups = max(0, $groupsCount - $userProgress->count());
        $notStarted += $unrecordedGroups;

        return [
            'groups' => $groupProgressMap,
            'counts' => [
                'all' => $groupsCount,
                'ready' => $ready,
                'cooldown' => $cooldown,
                'not_started' => $notStarted,
                'completed' => $completed,
            ],
        ];
    }
}
