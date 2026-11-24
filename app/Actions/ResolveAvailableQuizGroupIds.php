<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\User;

readonly class ResolveAvailableQuizGroupIds
{
    /**
     * Resolve available passed group IDs for a member or guest.
     *
     * @param  array<int, int>  $passedGroupIds
     * @return array<int, int>
     */
    public function handle(?User $user, array $passedGroupIds = []): array
    {
        if ($user instanceof User) {
            /** @var array<int, int> $availableGroupIds */
            $availableGroupIds = LearningProgress::query()
                ->where('user_id', $user->id)
                ->where('stage', '>=', 1)
                ->pluck('group_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();

            return $availableGroupIds;
        }

        if ($passedGroupIds === []) {
            return [];
        }

        /** @var array<int, int> $availableGroupIds */
        $availableGroupIds = Group::query()
            ->whereIn('id', $passedGroupIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        return $availableGroupIds;
    }
}
