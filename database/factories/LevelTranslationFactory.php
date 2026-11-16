<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Locale;
use App\Models\Level;
use App\Models\LevelTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LevelTranslation>
 */
class LevelTranslationFactory extends Factory
{
    protected $model = LevelTranslation::class;

    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'locale' => Locale::default()->value,
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
