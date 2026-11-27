<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\Level;
use App\Models\SentenceClozeQuestion;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Illuminate\Support\Facades\Config;

test('vocabulary translations relation returns all translations belonging to the vocabulary', function () {
    $vocabulary = Vocabulary::factory()
        ->withTranslation('zh_TW', '綿羊', '綿羊正在吃草。')
        ->withTranslation('ja', '羊', '羊が草を食べている。')
        ->withTranslation('zh_CN', '绵羊', '绵羊正在吃草。')
        ->create(['word' => 'sheep']);

    Vocabulary::factory()
        ->withTranslation('zh_TW', '蘋果', '這是一顆蘋果。')
        ->create(['word' => 'apple']);

    $translations = $vocabulary->translations()->orderBy('locale')->get();

    expect($translations)
        ->toHaveCount(3)
        ->each->toBeInstanceOf(VocabularyTranslation::class);

    expect($translations->pluck('definition', 'locale')->all())->toBe([
        'ja' => '羊',
        'zh_CN' => '绵羊',
        'zh_TW' => '綿羊',
    ]);
});

test('vocabulary translation relation returns the translation matching active or specified locale', function () {
    $vocabulary = Vocabulary::factory()
        ->withTranslation('zh_TW', '綿羊', '綿羊正在吃草。')
        ->withTranslation('ja', '羊', '羊が草を食べている。')
        ->create(['word' => 'sheep']);

    app()->setLocale('ja');

    $activeTranslation = $vocabulary->translation;
    $zhTwTranslation = $vocabulary->translation('zh_TW')->first();
    $missingTranslation = $vocabulary->translation('en_US')->first();

    expect($activeTranslation)
        ->toBeInstanceOf(VocabularyTranslation::class)
        ->locale->toBe('ja')
        ->definition->toBe('羊')
        ->example_translation->toBe('羊が草を食べている。')->and(
            $zhTwTranslation,
        )->toBeInstanceOf(VocabularyTranslation::class)
        ->locale->toBe('zh_TW')
        ->definition->toBe('綿羊')->and($missingTranslation)->toBeNull();
});

test('vocabulary returns localized definition and example translation from eager-loaded translation relation', function () {
    $vocabulary = Vocabulary::factory()
        ->withTranslation('zh_TW', '綿羊', '綿羊正在吃草。')
        ->withTranslation('ja', '羊', '羊が草を食べている。')
        ->withTranslation('zh_CN', '绵羊', '绵羊正在吃草。')
        ->create(['word' => 'sheep']);

    app()->setLocale('ja');
    $jaVocabulary = Vocabulary::query()->with('translation')->findOrFail($vocabulary->id);

    expect($jaVocabulary->definition)
        ->toBe('羊')
        ->and($jaVocabulary->example_translation)
        ->toBe('羊が草を食べている。');

    app()->setLocale('zh_CN');
    $zhCnVocabulary = Vocabulary::query()->with('translation')->findOrFail($vocabulary->id);

    expect($zhCnVocabulary->definition)
        ->toBe('绵羊')
        ->and($zhCnVocabulary->example_translation)
        ->toBe('绵羊正在吃草。');
});

test('vocabulary level and group relations return the parent level and group', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '繁中描述')
        ->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
        'sequence' => 4,
    ]);
    $vocabulary = Vocabulary::factory()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'word' => 'forest',
    ]);

    expect($vocabulary->level)
        ->toBeInstanceOf(Level::class)
        ->id->toBe($level->id)
        ->name->toBe('基礎入門')->and($vocabulary->group)->toBeInstanceOf(Group::class)
        ->id->toBe($group->id)->and($vocabulary->group->sequence)->toBe(4);
});

test('vocabulary sentenceClozeQuestion relation returns the associated question or null when absent', function () {
    $vocabularyWithQuestion = Vocabulary::factory()->create(['word' => 'ocean']);
    $vocabularyWithoutQuestion = Vocabulary::factory()->create(['word' => 'desert']);

    SentenceClozeQuestion::factory()->create([
        'vocabulary_id' => $vocabularyWithQuestion->id,
        'sentence' => 'The {{blank}} is vast and deep.',
        'blank_word' => 'ocean',
        'distractors' => [
            ['vocabulary_id' => 10, 'word' => 'lake'],
            ['vocabulary_id' => 11, 'word' => 'pond'],
            ['vocabulary_id' => 12, 'word' => 'river'],
        ],
    ]);

    expect($vocabularyWithQuestion->sentenceClozeQuestion)
        ->toBeInstanceOf(SentenceClozeQuestion::class)
        ->sentence->toBe('The {{blank}} is vast and deep.')
        ->blank_word->toBe('ocean')
        ->distractors->toHaveCount(3)->and($vocabularyWithoutQuestion->sentenceClozeQuestion)->toBeNull();
});

test('vocabulary audio url accessors resolve configured r2 url and fallback to default cdn', function () {
    $vocabulary = Vocabulary::factory()->create(['word' => 'river']);

    Config::set('filesystems.disks.r2.url', 'https://cdn.example.com/');

    expect($vocabulary->word_audio_url)
        ->toBe("https://cdn.example.com/vocabulary/{$vocabulary->id}/word.mp3")
        ->and($vocabulary->sentence_audio_url)
        ->toBe("https://cdn.example.com/vocabulary/{$vocabulary->id}/sentence.mp3");

    Config::set('filesystems.disks.r2.url', '');

    expect($vocabulary->word_audio_url)
        ->toBe("https://assets.vomorize.com/vocabulary/{$vocabulary->id}/word.mp3")
        ->and($vocabulary->sentence_audio_url)
        ->toBe("https://assets.vomorize.com/vocabulary/{$vocabulary->id}/sentence.mp3");
});
