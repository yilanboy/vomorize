<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Locale;
use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
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
            'example_sentence' => 'This is a test sentence.',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Vocabulary $vocabulary): void {
            foreach (Locale::values() as $locale) {
                if ($vocabulary->translations()->where('locale', $locale)->doesntExist()) {
                    VocabularyTranslation::factory()->create([
                        'vocabulary_id' => $vocabulary->id,
                        'locale' => $locale,
                    ]);
                }
            }
        });
    }

    public function withTranslation(
        string $locale = 'zh_TW',
        ?string $definition = null,
        ?string $exampleTranslation = null,
    ): static {
        return $this->afterCreating(function (Vocabulary $vocabulary) use ($locale, $definition, $exampleTranslation): void {
            VocabularyTranslation::query()->updateOrCreate(
                [
                    'vocabulary_id' => $vocabulary->id,
                    'locale' => $locale,
                ],
                [
                    'definition' => $definition ?? '測試定義',
                    'example_translation' => $exampleTranslation ?? '這是一句測試句子。',
                ],
            );
        });
    }
}
