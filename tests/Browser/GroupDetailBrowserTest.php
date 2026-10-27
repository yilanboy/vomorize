<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;

it("won't show footer", function () {
    $level = Level::factory()->create([
        'name' => '基礎入門',
    ]);

    $group = Group::factory()->create([
        'level_id' => $level->id,
        'sequence' => 1,
    ]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $user = User::factory()->create();

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
    ]);

    $this->actingAs($user);

    visit(route('groups.show', $group))
        ->assertNotPresent('css=footer')
        ->assertPresent('aside[aria-label="Group action footer"]');
});
