<?php

namespace App\Actions;

use App\Models\Level;
use Illuminate\Support\Collection;

readonly class GetLevels
{
    /**
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     description: string,
     *     vocabularies_count: int,
     *     groups_count: int,
     * }>
     */
    public function handle(): Collection
    {
        $levels = Level::query()
            ->withCount(['vocabularies', 'groups'])
            ->orderBy('id', 'asc')
            ->get(['id', 'name', 'description']);

        /** @var Collection<int, array{id: int, name: string, description: string, vocabularies_count: int, groups_count: int}> */
        return $levels->map(fn (Level $level): array => [
            'id' => $level->id,
            'name' => $level->name,
            'description' => $level->description,
            'vocabularies_count' => (int) ($level->vocabularies_count ?? 1000),
            'groups_count' => (int) ($level->groups_count ?? 100),
        ]);
    }
}
