<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /**
     * @var array<int, int>
     */
    protected static array $sequences = [];

    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'sequence' => function (array $attributes): int {
                $levelId = (int) ($attributes['level_id'] ?? 0);

                if (! isset(static::$sequences[$levelId])) {
                    static::$sequences[$levelId] = (int) (Group::query()->where('level_id', $levelId)->max('sequence') ?? 0);
                }

                return ++static::$sequences[$levelId];
            },
        ];
    }
}
