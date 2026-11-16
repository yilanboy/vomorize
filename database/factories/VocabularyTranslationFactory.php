<?php

declare(strict_types=1);

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
    protected $model = VocabularyTranslation::class;

    public function definition(): array
    {
        return [
            'vocabulary_id' => Vocabulary::factory(),
            'locale' => Locale::default()->value,
            'definition' => '測試定義',
            'example_translation' => '這是一句測試句子。',
        ];
    }
}
