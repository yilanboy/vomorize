<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Illuminate\Database\Seeder;

class DefaultLevelVocabularySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(int $levelId): void
    {
        $level = Level::findOrFail($levelId);

        /**
         * @var array<string, array<int, array{
         *     word: string,
         *     part_of_speech: string,
         *     pronunciation: string,
         *     example_sentence: string,
         *     translations: array<string, array{definition: string, example_translation: string}>
         * }>> $levelVocabulary
         */
        $levelVocabulary = require database_path('data/vocabulary/level_'.$levelId.'.php');

        foreach ($levelVocabulary as $key => $items) {
            $group = Group::updateOrCreate([
                'level_id' => $level->id,
                'sequence' => str_replace('group_', '', $key),
            ]);

            $vocabularyRows = [];

            foreach ($items as $item) {
                $vocabularyRows[] = [
                    'group_id' => $group->id,
                    'word' => $item['word'],
                    'part_of_speech' => $item['part_of_speech'],
                    'pronunciation' => $item['pronunciation'],
                    'example_sentence' => $item['example_sentence'],
                ];
            }

            Vocabulary::upsert(
                $vocabularyRows,
                uniqueBy: ['group_id', 'word'],
                update: ['part_of_speech', 'pronunciation', 'example_sentence'],
            );

            $vocabularies = Vocabulary::query()
                ->where('group_id', $group->id)
                ->get(['id', 'word'])
                ->keyBy('word');

            $translationRows = [];

            foreach ($items as $item) {
                foreach ($item['translations'] as $locale => $translation) {
                    $translationRows[] = [
                        'vocabulary_id' => $vocabularies[$item['word']]->id,
                        'locale' => $locale,
                        ...$translation,
                    ];
                }
            }

            VocabularyTranslation::upsert(
                $translationRows,
                uniqueBy: ['vocabulary_id', 'locale'],
                update: ['definition', 'example_translation'],
            );
        }
    }
}
