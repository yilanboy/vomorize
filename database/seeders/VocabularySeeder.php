<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Locale;
use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Illuminate\Database\Seeder;
use RuntimeException;

class VocabularySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now()->toDateTimeString();

        for ($levelId = 1; $levelId <= 7; $levelId++) {
            $expectedVocabularyIds = $this->seedLevelBase($levelId, $timestamp);
            $this->seedLevelTranslations($levelId, $expectedVocabularyIds, $timestamp);
            gc_collect_cycles();
        }
    }

    /**
     * @return list<int>
     */
    private function seedLevelBase(int $levelId, string $timestamp): array
    {
        $baseFile = database_path("data/level_{$levelId}_vocabulary.php");

        if (! file_exists($baseFile)) {
            throw new RuntimeException("Missing base vocabulary data file: {$baseFile}");
        }

        $level = Level::findOrFail($levelId);

        /**
         * @var array<string, array<int, array{
         *     id: int,
         *     word: string,
         *     part_of_speech: string,
         *     pronunciation: string,
         *     example_sentence: string
         * }>> $levelVocabulary
         */
        $levelVocabulary = require $baseFile;
        $expectedVocabularyIds = [];

        foreach ($levelVocabulary as $key => $items) {
            $sequence = (int) str_replace('group_', '', $key);

            $group = Group::updateOrCreate([
                'level_id' => $level->id,
                'sequence' => $sequence,
            ]);

            $vocabularyRows = [];

            foreach ($items as $item) {
                $expectedVocabularyIds[] = $item['id'];
                $vocabularyRows[] = [
                    'id' => $item['id'],
                    'level_id' => $level->id,
                    'group_id' => $group->id,
                    'word' => $item['word'],
                    'part_of_speech' => $item['part_of_speech'],
                    'pronunciation' => $item['pronunciation'],
                    'example_sentence' => $item['example_sentence'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if ($vocabularyRows !== []) {
                Vocabulary::upsert(
                    $vocabularyRows,
                    uniqueBy: ['id'],
                    update: ['level_id', 'group_id', 'word', 'part_of_speech', 'pronunciation', 'example_sentence', 'updated_at'],
                );
            }
        }

        return $expectedVocabularyIds;
    }

    /**
     * @param  list<int>  $expectedVocabularyIds
     */
    private function seedLevelTranslations(int $levelId, array $expectedVocabularyIds, string $timestamp): void
    {
        foreach (Locale::values() as $locale) {
            $file = database_path("data/{$locale}/level_{$levelId}_vocabulary.php");

            if (! file_exists($file)) {
                throw new RuntimeException("Missing vocabulary translation file for locale [{$locale}] level [{$levelId}]: {$file}");
            }

            /**
             * @var array<int, array{
             *     definition: string,
             *     example_translation: string
             * }> $translationsById
             */
            $translationsById = require $file;

            $translationRows = [];

            foreach ($expectedVocabularyIds as $vocabularyId) {
                if (! isset($translationsById[$vocabularyId])
                    || trim($translationsById[$vocabularyId]['definition']) === ''
                    || trim($translationsById[$vocabularyId]['example_translation']) === ''
                ) {
                    throw new RuntimeException(
                        "Missing or empty vocabulary translation for vocabulary [{$vocabularyId}] in level [{$levelId}] locale [{$locale}].",
                    );
                }

                $translationRows[] = [
                    'vocabulary_id' => $vocabularyId,
                    'locale' => $locale,
                    'definition' => $translationsById[$vocabularyId]['definition'],
                    'example_translation' => $translationsById[$vocabularyId]['example_translation'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            foreach (array_chunk($translationRows, 500) as $chunk) {
                VocabularyTranslation::upsert(
                    $chunk,
                    uniqueBy: ['vocabulary_id', 'locale'],
                    update: ['definition', 'example_translation', 'updated_at'],
                );
            }
        }
    }
}
