<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;

test('user learningProgress relation returns learning progress records belonging to the user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $level = Level::factory()->create();
    $group1 = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);
    $group2 = Group::factory()->create(['level_id' => $level->id, 'sequence' => 2]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group1->id,
        'stage' => 3,
        'last_score' => 80,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group2->id,
        'stage' => 6,
        'last_score' => 100,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $otherUser->id,
        'level_id' => $level->id,
        'group_id' => $group1->id,
        'stage' => 1,
    ]);

    $progress = $user->learningProgress;

    expect($progress)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(LearningProgress::class)
        ->and($progress->pluck('last_score')->sort()->values()->all())
        ->toBe([80, 100]);

});
