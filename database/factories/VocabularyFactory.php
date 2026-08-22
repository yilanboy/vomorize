<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Vocabulary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vocabulary>
 */
class VocabularyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'word' => $this->faker->word,
            'part_of_speech' => $this->faker->randomElement(['noun', 'verb', 'adjective', 'adverb']),
            'pronunciation' => $this->faker->word,
            'example_sentence' => $this->faker->sentence,
        ];
    }
}
