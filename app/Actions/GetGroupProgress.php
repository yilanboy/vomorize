<?php

namespace App\Actions;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\User;

readonly class GetGroupProgress
{
    public function __construct(
        private DetermineGroupStatus $determineGroupStatus,
    ) {}

    /**
     * Calculate learning progress for an authenticated user on a group.
     *
     * @return array{
     *     status: string,
     *     stage: int,
     *     next_review_at: string|null,
     *     cooldown_remaining_seconds: int|null,
     * }
     */
    public function handle(Group $group, User $user): array
    {
        /** @var LearningProgress|null $progress */
        $progress = $user->learningProgress()->where('group_id', $group->id)->first();

        $statusResult = $this->determineGroupStatus->handle($progress, now());
        $stage = $progress->stage ?? 0;

        return [
            'status' => $statusResult['status']->value,
            'stage' => $stage,
            'next_review_at' => $progress?->next_review_at?->toIso8601String(),
            'cooldown_remaining_seconds' => $statusResult['cooldown_remaining_seconds'],
        ];
    }
}
