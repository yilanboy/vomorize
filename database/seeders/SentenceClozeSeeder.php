<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SentenceClozeQuestion;
use Illuminate\Database\Seeder;
use RuntimeException;

class SentenceClozeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now()->toDateTimeString();

        for ($levelId = 1; $levelId <= 7; $levelId++) {
            $this->seedLevel($levelId, $timestamp);
            gc_collect_cycles();
        }
    }

    private function seedLevel(int $levelId, string $timestamp): void
    {
        $file = database_path("data/level_{$levelId}_sentence_cloze.php");

        if (! file_exists($file)) {
            throw new RuntimeException("Missing sentence cloze data file: {$file}");
        }

        /**
         * @var array<string, array<int, array{
         *     vocabulary_id: int,
         *     sentence: string,
         *     blank_word: string,
         *     distractors: array<int, array{vocabulary_id: int, word: string}>
         * }>> $levelQuestions
         */
        $levelQuestions = require $file;

        foreach ($levelQuestions as $items) {
            $rows = [];

            foreach ($items as $item) {
                $rows[] = [
                    'vocabulary_id' => $item['vocabulary_id'],
                    'sentence' => $item['sentence'],
                    'blank_word' => $item['blank_word'],
                    'distractors' => json_encode($item['distractors'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            SentenceClozeQuestion::upsert(
                $rows,
                uniqueBy: ['vocabulary_id'],
                update: ['sentence', 'blank_word', 'distractors', 'updated_at'],
            );
        }
    }
}
