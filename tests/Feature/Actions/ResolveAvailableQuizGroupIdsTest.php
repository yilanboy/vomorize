<?php

declare(strict_types=1);

use App\Actions\ResolveAvailableQuizGroupIds;
use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;

it('returns passed group ids for an authenticated member ignoring unpassed and other users progress', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $level = Level::factory()->create();
    $groups = Group::factory()->count(4)->create(['level_id' => $level->id]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 1,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[1]->id,
        'stage' => 4,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[2]->id,
        'stage' => 0,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $otherUser->id,
        'level_id' => $level->id,
        'group_id' => $groups[3]->id,
        'stage' => 2,
    ]);

    $action = app(ResolveAvailableQuizGroupIds::class);

    $resolved = $action->handle($user, [$groups[2]->id, $groups[3]->id]);

    expect($resolved)->toEqualCanonicalizing([$groups[0]->id, $groups[1]->id]);
});

it('returns only existing group ids from passed list for a guest', function () {
    $level = Level::factory()->create();
    $groups = Group::factory()->count(2)->create(['level_id' => $level->id]);

    $action = app(ResolveAvailableQuizGroupIds::class);

    $resolved = $action->handle(null, [$groups[0]->id, $groups[1]->id, 999999]);

    expect($resolved)->toEqualCanonicalizing([$groups[0]->id, $groups[1]->id]);
});

it('returns an empty array for a guest with no passed group ids', function () {
    $action = app(ResolveAvailableQuizGroupIds::class);

    expect($action->handle(null, []))->toBe([]);
});
