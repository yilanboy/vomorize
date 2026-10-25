<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;

test('stage 1 intro page loads without javascript errors', function () {
    $level = Level::factory()->create(['name' => '基礎入門']);
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    visit("/groups/{$group->id}/intro")
        ->assertSee($group->sequence)
        ->assertNoSmoke();
});

test('group show page for passed group loads without javascript errors', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
    ]);

    $this->actingAs($user);

    visit("/groups/{$group->id}")
        ->assertNoSmoke();
});
