<?php

namespace Database\Factories;

use App\Enums\Locale;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VocabularyTranslation>
 */
class VocabularyTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vocabulary_id' => Vocabulary::factory(),
            'locale' => $this->faker->randomElement(Locale::values()),
            'definition' => $this->faker->word,
            'example_translation' => $this->faker->sentence,
        ];
    }
}
