<?php

declare(strict_types=1);

use App\Models\SentenceClozeQuestion;
use Database\Seeders\LevelSeeder;
use Database\Seeders\SentenceClozeSeeder;
use Database\Seeders\VocabularySeeder;

test('sentence cloze seeder creates 7000 questions and is idempotent', function () {
    $this->seed(LevelSeeder::class);
    $this->seed(VocabularySeeder::class);
    $this->seed(SentenceClozeSeeder::class);

    expect(SentenceClozeQuestion::count())->toBe(7000);

    $question = SentenceClozeQuestion::where('vocabulary_id', 37)->firstOrFail();
    expect($question->sentence)->toContain('{{blank}}')
        ->and($question->blank_word)->toBe('appeared')
        ->and($question->distractors)->toBeArray()
        ->and(count($question->distractors))->toBeGreaterThanOrEqual(3);

    $this->seed(SentenceClozeSeeder::class);

    expect(SentenceClozeQuestion::count())->toBe(7000);
});
