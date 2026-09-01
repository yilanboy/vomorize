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

    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'sequence' => $this->faker->numberBetween(1, 10),
        ];
    }
}
