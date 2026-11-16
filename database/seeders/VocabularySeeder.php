<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Locale;
use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Illuminate\Database\Seeder;

class VocabularySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now()->toDateTimeString();

        for ($levelId = 1; $levelId <= 7; $levelId++) {
            $this->seedLevelBase($levelId, $timestamp);
            $this->seedLevelTranslations($levelId, $timestamp);
            gc_collect_cycles();
        }
    }

    private function seedLevelBase(int $levelId, string $timestamp): void
    {
        $baseFile = database_path("data/zh_TW/level_{$levelId}_vocabulary.php");

        if (! file_exists($baseFile)) {
            return;
        }

        $level = Level::findOrFail($levelId);

        /**
         * @var array<string, array<int, array{
         *     id: int,
         *     word: string,
         *     part_of_speech: string,
         *     pronunciation: string,
         *     definition: string,
         *     example_sentence: string,
         *     example_translation: string
         * }>> $levelVocabulary
         */
        $levelVocabulary = require $baseFile;

        foreach ($levelVocabulary as $key => $items) {
            $sequence = (int) str_replace('group_', '', $key);

            $group = Group::updateOrCreate([
                'level_id' => $level->id,
                'sequence' => $sequence,
            ]);

            $vocabularyRows = [];

            foreach ($items as $item) {
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
    }

    private function seedLevelTranslations(int $levelId, string $timestamp): void
    {
        foreach (Locale::values() as $locale) {
            $file = database_path("data/{$locale}/level_{$levelId}_vocabulary.php");

            if (! file_exists($file)) {
                continue;
            }

            /**
             * @var array<string, array<int, array{
             *     id: int,
             *     word: string,
             *     part_of_speech: string,
             *     pronunciation: string,
             *     definition: string,
             *     example_sentence: string,
             *     example_translation: string
             * }>> $levelVocabulary
             */
            $levelVocabulary = require $file;

            $translationRows = [];

            foreach ($levelVocabulary as $items) {
                foreach ($items as $item) {
                    $translationRows[] = [
                        'vocabulary_id' => $item['id'],
                        'locale' => $locale,
                        'definition' => $item['definition'],
                        'example_translation' => $item['example_translation'],
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
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
