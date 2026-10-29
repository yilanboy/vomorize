<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Level>
 */
class LevelFactory extends Factory
{
    protected $model = Level::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                '基礎入門',
                '初級進階',
                '中級應用',
                '中高級加強',
                '高級深造',
                '大師精通',
                '額外補充',
            ]),
            'description' => $this->faker->sentence(),
        ];
    }
}
