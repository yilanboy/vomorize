<?php

declare(strict_types=1);

use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;

test('vocabulary translation vocabulary relation returns the parent vocabulary', function () {
    $vocabulary = Vocabulary::factory()
        ->withTranslation('zh_TW', '綿羊', '綿羊正在田野裡吃草。')
        ->create(['word' => 'sheep']);

    $translation = VocabularyTranslation::query()
        ->where('vocabulary_id', $vocabulary->id)
        ->where('locale', 'zh_TW')
        ->firstOrFail();

    $parentVocabulary = $translation->vocabulary;

    expect($parentVocabulary)
        ->toBeInstanceOf(Vocabulary::class)
        ->id->toBe($vocabulary->id)
        ->word->toBe('sheep');
});
