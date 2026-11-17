<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\LevelTranslation;
use App\Models\User;
use App\Models\Vocabulary;

test('level translations relation returns all translations belonging to the level', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '核心常用單字')
        ->withTranslation('ja', '基礎入門 (JA)', '基本単語')
        ->withTranslation('zh_CN', '基础入门', '核心常用单词')
        ->create();

    Level::factory()
        ->withTranslation('zh_TW', '其他等級', '其他描述')
        ->create();

    $translations = $level->translations()->orderBy('locale')->get();

    expect($translations)
        ->toHaveCount(3)
        ->each->toBeInstanceOf(LevelTranslation::class)
        ->and($translations->pluck('name', 'locale')->all())
        ->toBe([
            'ja' => '基礎入門 (JA)',
            'zh_CN' => '基础入门',
            'zh_TW' => '基礎入門',
        ]);

});

test('level translation relation returns the translation matching active or specified locale', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '繁中描述')
        ->withTranslation('ja', '基礎入門 (JA)', '日本語説明')
        ->create();

    app()->setLocale('ja');

    $activeTranslation = $level->translation;
    $zhTwTranslation = $level->translation('zh_TW')->first();
    $missingTranslation = $level->translation('en_US')->first();

    expect($activeTranslation)
        ->toBeInstanceOf(LevelTranslation::class)
        ->locale->toBe('ja')
        ->name->toBe('基礎入門 (JA)')
        ->description->toBe('日本語説明');

    expect($zhTwTranslation)
        ->toBeInstanceOf(LevelTranslation::class)
        ->locale->toBe('zh_TW')
        ->name->toBe('基礎入門');

    expect($missingTranslation)->toBeNull();
});

test('level returns localized name and description from eager-loaded translation relation', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '繁中描述')
        ->withTranslation('ja', '基礎入門 (JA)', '日本語説明')
        ->withTranslation('zh_CN', '基础入门', '简中描述')
        ->create();

    app()->setLocale('ja');
    $jaLevel = Level::query()->with('translation')->findOrFail($level->id);

    expect($jaLevel->name)->toBe('基礎入門 (JA)')
        ->and($jaLevel->description)->toBe('日本語説明');

    app()->setLocale('zh_CN');
    $zhCnLevel = Level::query()->with('translation')->findOrFail($level->id);

    expect($zhCnLevel->name)->toBe('基础入门')
        ->and($zhCnLevel->description)->toBe('简中描述');
});

test('level groups relation returns groups ordered by sequence ascending', function () {
    $level = Level::factory()->create();
    $otherLevel = Level::factory()->create();

    Group::factory()->create(['level_id' => $level->id, 'sequence' => 3]);
    Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);
    Group::factory()->create(['level_id' => $level->id, 'sequence' => 2]);
    Group::factory()->create(['level_id' => $otherLevel->id, 'sequence' => 1]);

    $groups = $level->groups;

    expect($groups)
        ->toHaveCount(3)
        ->each->toBeInstanceOf(Group::class);

    expect($groups->pluck('sequence')->all())->toBe([1, 2, 3]);
});

test('level vocabularies relation returns vocabularies belonging to the level', function () {
    $level = Level::factory()->create();
    $otherLevel = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);
    $otherGroup = Group::factory()->create(['level_id' => $otherLevel->id, 'sequence' => 1]);

    Vocabulary::factory()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'word' => 'apple',
    ]);
    Vocabulary::factory()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'word' => 'banana',
    ]);
    Vocabulary::factory()->create([
        'level_id' => $otherLevel->id,
        'group_id' => $otherGroup->id,
        'word' => 'cherry',
    ]);

    $vocabularies = $level->vocabularies;

    expect($vocabularies)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(Vocabulary::class);

    expect($vocabularies->pluck('word')->sort()->values()->all())->toBe(['apple', 'banana']);
});

test('level learningProgress relation returns learning progress records for the level', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $otherLevel = Level::factory()->create();
    $group1 = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);
    $group2 = Group::factory()->create(['level_id' => $level->id, 'sequence' => 2]);
    $otherGroup = Group::factory()->create(['level_id' => $otherLevel->id, 'sequence' => 1]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group1->id,
        'stage' => 2,
        'last_score' => 90,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group2->id,
        'stage' => 4,
        'last_score' => 100,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $otherLevel->id,
        'group_id' => $otherGroup->id,
        'stage' => 6,
    ]);

    $progress = $level->learningProgress;

    expect($progress)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(LearningProgress::class);

    expect($progress->pluck('stage')->sort()->values()->all())->toBe([2, 4]);
});
