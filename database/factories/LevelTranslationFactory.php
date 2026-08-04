<?php

namespace Database\Factories;

use App\Models\Level;
use App\Models\LevelTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LevelTranslation>
 */
class LevelTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'locale' => fake()->locale(),
            'name' => fake()->name(),
            'description' => fake()->text(),
        ];
    }
}
