<?php

namespace App\Actions;

use App\Models\Group;

readonly class GetGroupDetail
{
    /**
     * Retrieve the objective group details.
     *
     * @return array{
     *     id: int,
     *     sequence: int,
     *     level_id: int,
     *     level_name: string,
     *     vocabularies_count: int,
     *     prev_group_id: int|null,
     *     next_group_id: int|null,
     * }
     */
    public function handle(Group $group): array
    {
        $group->loadMissing('level');
        $group->loadCount('vocabularies');

        $prevGroupId = Group::query()
            ->where('level_id', $group->level_id)
            ->where('sequence', '<', $group->sequence)
            ->orderByDesc('sequence')
            ->value('id');

        $nextGroupId = Group::query()
            ->where('level_id', $group->level_id)
            ->where('sequence', '>', $group->sequence)
            ->orderBy('sequence')
            ->value('id');

        return [
            'id' => $group->id,
            'sequence' => $group->sequence,
            'level_id' => $group->level_id,
            'level_name' => $group->level->name,
            'vocabularies_count' => (int) ($group->vocabularies_count ?? 10),
            'prev_group_id' => $prevGroupId,
            'next_group_id' => $nextGroupId,
        ];
    }
}
