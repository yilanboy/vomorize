<?php

namespace App\Actions;

use App\Enums\GroupStatus;
use Illuminate\Support\Collection;

class CalculateGroupStatusCounts
{
    /**
     * @param  Collection<int, array{status: string, ...}>  $groups
     * @return array{
     *     all: int,
     *     ready: int,
     *     cooldown: int,
     *     not_started: int,
     *     completed: int
     * }
     */
    public function handle(Collection $groups): array
    {
        $counts = $groups->countBy('status');

        return [
            'all' => $groups->count(),
            'ready' => $counts->get(GroupStatus::Ready->value, 0),
            'cooldown' => $counts->get(GroupStatus::Cooldown->value, 0),
            'not_started' => $counts->get(GroupStatus::NotStarted->value, 0),
            'completed' => $counts->get(GroupStatus::Completed->value, 0),
        ];
    }
}
