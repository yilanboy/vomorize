<?php

use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Database\Seeders\VocabularySeeder;

it('seeds all levels, groups, vocabularies and translations', function () {
    $this->seed(VocabularySeeder::class);

    expect(Level::count())->toBe(7)
        ->and(Group::count())->toBe(700)
        ->and(Vocabulary::count())->toBe(7000)
        ->and(VocabularyTranslation::where('locale', 'zh_TW')->count())->toBe(7000);
});

it('seeds vocabulary fields and translations correctly', function () {
    $this->seed(VocabularySeeder::class);

    $group = Group::where('level_id', 1)->where('sequence', 1)->firstOrFail();
    $vocabulary = Vocabulary::where('group_id', $group->id)->where('word', 'sign')->firstOrFail();

    expect($vocabulary->part_of_speech)->toBe('n./v.')
        ->and($vocabulary->pronunciation)->toBe('/ˈsaɪn/')
        ->and($vocabulary->example_sentence)->toBe('Please sign your name at the bottom of this legal document.');

    $translation = $vocabulary->translations()->where('locale', 'zh_TW')->firstOrFail();

    expect($translation->definition)->toBe('簽名')
        ->and($translation->example_translation)->toBe('請在這份法律文件的底部簽名。');
});

it('does not duplicate or reassign ids when run twice', function () {
    $this->seed(VocabularySeeder::class);

    $vocabularyId = Vocabulary::where('word', 'sign')->firstOrFail()->id;

    $this->seed(VocabularySeeder::class);

    expect(Level::count())->toBe(7)
        ->and(Group::count())->toBe(700)
        ->and(Vocabulary::count())->toBe(7000)
        ->and(VocabularyTranslation::where('locale', 'zh_TW')->count())->toBe(7000)
        ->and(Vocabulary::where('word', 'sign')->firstOrFail()->id)->toBe($vocabularyId);
});
