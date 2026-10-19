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
     * }
     */
    public function handle(Group $group): array
    {
        $group->loadMissing('level');
        $group->loadCount('vocabularies');

        return [
            'id' => $group->id,
            'sequence' => $group->sequence,
            'level_id' => $group->level_id,
            'level_name' => $group->level->name,
            'vocabularies_count' => (int) ($group->vocabularies_count ?? 10),
        ];
    }
}
