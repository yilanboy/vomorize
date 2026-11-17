<?php

declare(strict_types=1);

use App\Models\SentenceClozeQuestion;
use App\Models\Vocabulary;

test('sentence cloze question vocabulary relation returns the parent vocabulary', function () {
    $vocabulary = Vocabulary::factory()->create(['word' => 'journey']);

    $question = SentenceClozeQuestion::factory()->create([
        'vocabulary_id' => $vocabulary->id,
        'sentence' => 'Life is a long {{blank}}.',
        'blank_word' => 'journey',
    ]);

    $parentVocabulary = $question->vocabulary;

    expect($parentVocabulary)
        ->toBeInstanceOf(Vocabulary::class)
        ->id->toBe($vocabulary->id)
        ->word->toBe('journey');
});
