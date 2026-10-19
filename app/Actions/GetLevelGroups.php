<?php

namespace App\Actions;

use App\Models\Group;
use App\Models\Level;
use Illuminate\Support\Collection;

readonly class GetLevelGroups
{
    /**
     * @return Collection<int, array{
     *     id: int,
     *     sequence: int,
     *     vocabularies_count: int,
     * }>
     */
    public function handle(Level $level): Collection
    {
        $groups = $level->groups()->orderBy('sequence', 'asc')->get(['id', 'sequence']);

        /** @var Collection<int, array{id: int, sequence: int, vocabularies_count: int}> */
        return $groups->map(fn (Group $group): array => [
            'id' => $group->id,
            'sequence' => $group->sequence,
            'vocabularies_count' => 10,
        ]);
    }
}
