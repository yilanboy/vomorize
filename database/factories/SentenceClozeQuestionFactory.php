<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SentenceClozeQuestion;
use App\Models\Vocabulary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SentenceClozeQuestion>
 */
class SentenceClozeQuestionFactory extends Factory
{
    protected $model = SentenceClozeQuestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vocabulary_id' => Vocabulary::factory(),
            'sentence' => 'This is a {{blank}} sentence.',
            'blank_word' => 'test',
            'distractors' => [
                ['vocabulary_id' => 1, 'word' => 'alpha'],
                ['vocabulary_id' => 2, 'word' => 'beta'],
                ['vocabulary_id' => 3, 'word' => 'gamma'],
            ],
        ];
    }
}
