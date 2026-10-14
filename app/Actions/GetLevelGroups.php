<?php

namespace App\Actions;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Collection;

readonly class GetLevelGroups
{
    public function __construct(
        private DetermineGroupStatus $determineGroupStatus
    ) {}

    /**
     * @return Collection<int, array{
     *     id: int,
     *     sequence: int,
     *     status: string,
     *     stage: int,
     *     next_review_at: string|null,
     *     cooldown_remaining_seconds: int|null,
     *     vocabularies_count: int
     * }>
     */
    public function handle(Level $level, ?User $user): Collection
    {
        $groups = $level->groups()
            ->orderBy('sequence', 'asc')
            ->get(['id', 'level_id', 'sequence']);

        $userProgress = $user instanceof User
            ? $user->learningProgress()
                ->where('level_id', $level->id)
                ->get()
                ->keyBy('group_id')
            : collect();

        $now = now();

        return $groups->map(function (Group $group) use ($userProgress, $now): array {
            /** @var LearningProgress|null $progress */
            $progress = $userProgress->get($group->id);
            $stage = (int) ($progress?->stage ?? 0);

            $statusResult = $this->determineGroupStatus->handle($progress, $now);

            return [
                'id' => $group->id,
                'sequence' => $group->sequence,
                'status' => $statusResult['status']->value,
                'stage' => $stage,
                'next_review_at' => $progress?->next_review_at?->toIso8601String(),
                'cooldown_remaining_seconds' => $statusResult['cooldown_remaining_seconds'],
                'vocabularies_count' => 10,
            ];
        });
    }
}
