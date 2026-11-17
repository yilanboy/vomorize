<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;

test('learning progress relations return the associated user, level, and group', function () {
    $user = User::factory()->create(['name' => 'Test Learner']);
    $level = Level::factory()
        ->withTranslation('zh_TW', '中級應用', '中級描述')
        ->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
        'sequence' => 7,
    ]);

    $progress = LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 4,
        'last_score' => 92,
    ]);

    expect($progress->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id)
        ->name->toBe('Test Learner');

    expect($progress->level)
        ->toBeInstanceOf(Level::class)
        ->id->toBe($level->id)
        ->name->toBe('中級應用');

    expect($progress->group)
        ->toBeInstanceOf(Group::class)
        ->id->toBe($group->id);

    expect($progress->group->sequence)->toBe(7);
});
