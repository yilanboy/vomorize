<?php

namespace App\Actions;

use App\Models\Level;

readonly class GetLevelDetail
{
    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     description: string,
     *     vocabularies_count: int,
     *     groups_count: int,
     * }
     */
    public function handle(Level $level): array
    {
        $level->loadCount(['vocabularies', 'groups']);

        return [
            'id' => $level->id,
            'name' => $level->name,
            'description' => $level->description,
            'vocabularies_count' => (int) ($level->vocabularies_count ?? 1000),
            'groups_count' => (int) ($level->groups_count ?? 100),
        ];
    }
}
