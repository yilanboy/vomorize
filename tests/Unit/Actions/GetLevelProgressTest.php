<?php

use App\Actions\DetermineGroupStatus;
use App\Actions\GetLevelProgress;
use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('returns counts and progress map for level groups', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();

    $groups = Group::factory()
        ->count(3)
        ->create([
            'level_id' => $level->id,
        ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 6,
    ]);

    $action = new GetLevelProgress(new DetermineGroupStatus);
    $result = $action->handle($level, $user);

    expect($result['counts'])
        ->toBe([
            'all' => 3,
            'ready' => 0,
            'cooldown' => 0,
            'not_started' => 2,
            'completed' => 1,
        ])
        ->and($result['groups'][$groups[0]->id]['status'])
        ->toBe('completed')
        ->and(isset($result['groups'][$groups[1]->id]))
        ->toBeFalse()
        ->and(isset($result['groups'][$groups[2]->id]))
        ->toBeFalse();
});
