<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;

test('group level relation returns the parent level', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '初級進階', '進階描述')
        ->create();

    $group = Group::factory()->create([
        'level_id' => $level->id,
        'sequence' => 1,
    ]);

    $parentLevel = $group->level;

    expect($parentLevel)
        ->toBeInstanceOf(Level::class)
        ->id->toBe($level->id)
        ->name->toBe('初級進階');
});

test('group vocabularies relation returns vocabularies belonging to the group', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);
    $otherGroup = Group::factory()->create(['level_id' => $level->id, 'sequence' => 2]);

    Vocabulary::factory()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'word' => 'river',
    ]);
    Vocabulary::factory()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'word' => 'mountain',
    ]);
    Vocabulary::factory()->create([
        'level_id' => $level->id,
        'group_id' => $otherGroup->id,
        'word' => 'ocean',
    ]);

    $vocabularies = $group->vocabularies;

    expect($vocabularies)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(Vocabulary::class)
        ->and($vocabularies->pluck('word')->sort()->values()->all())
        ->toBe(['mountain', 'river']);

});

test('group learningProgress relation returns learning progress records for the group', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);
    $otherGroup = Group::factory()->create(['level_id' => $level->id, 'sequence' => 2]);

    LearningProgress::factory()->create([
        'user_id' => $userA->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 3,
        'last_score' => 85,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $userB->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 5,
        'last_score' => 95,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $userA->id,
        'level_id' => $level->id,
        'group_id' => $otherGroup->id,
        'stage' => 1,
    ]);

    $progress = $group->learningProgress;

    expect($progress)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(LearningProgress::class)
        ->and($progress->pluck('last_score')->sort()->values()->all())
        ->toBe([85, 95]);

});
