<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use Illuminate\Database\Seeder;

class VocabularySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locale = config('app.locale', 'zh_TW');

        for ($levelId = 1; $levelId <= 7; $levelId++) {
            $file = database_path("data/{$locale}/level_{$levelId}_vocabulary.php");

            if (! file_exists($file)) {
                $file = database_path("data/zh_TW/level_{$levelId}_vocabulary.php");
            }

            if (! file_exists($file)) {
                continue;
            }

            $level = Level::findOrFail($levelId);

            /**
             * @var array<string, array<int, array{
             *     word: string,
             *     part_of_speech: string,
             *     pronunciation: string,
             *     definition: string,
             *     example_sentence: string,
             *     example_translation: string
             * }>> $levelVocabulary
             */
            $levelVocabulary = require $file;

            foreach ($levelVocabulary as $key => $items) {
                $sequence = (int) str_replace('group_', '', $key);

                $group = Group::updateOrCreate([
                    'level_id' => $level->id,
                    'sequence' => $sequence,
                ]);

                $vocabularyRows = [];

                foreach ($items as $item) {
                    $vocabularyRows[] = [
                        'level_id' => $level->id,
                        'group_id' => $group->id,
                        'word' => $item['word'],
                        'part_of_speech' => $item['part_of_speech'],
                        'pronunciation' => $item['pronunciation'],
                        'definition' => $item['definition'],
                        'example_sentence' => $item['example_sentence'],
                        'example_translation' => $item['example_translation'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                Vocabulary::upsert(
                    $vocabularyRows,
                    uniqueBy: ['group_id', 'word'],
                    update: ['level_id', 'part_of_speech', 'pronunciation', 'definition', 'example_sentence', 'example_translation', 'updated_at'],
                );
            }
        }
    }
}
