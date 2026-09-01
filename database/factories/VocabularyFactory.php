<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vocabulary>
 */
class VocabularyFactory extends Factory
{
    protected $model = Vocabulary::class;

    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'group_id' => Group::factory(),
            'word' => $this->faker->unique()->word(),
            'part_of_speech' => 'n.',
            'pronunciation' => '/test/',
            'definition' => '測試定義',
            'example_sentence' => 'This is a test sentence.',
            'example_translation' => '這是一句測試句子。',
        ];
    }
}
