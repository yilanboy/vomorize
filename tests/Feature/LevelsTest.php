<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guest can access levels page and sees inertia Levels component', function () {
    $response = $this->get(route('levels.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Levels')
    );
});

test('authenticated user can access levels page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('levels.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Levels')
    );
});

test('levels page shares ui translation keys for levels_page', function () {
    $response = $this->get(route('levels.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('translations.ui.levels_page.title')
        ->has('translations.ui.levels_page.subtitle')
        ->has('translations.ui.levels_page.level_badge')
        ->has('translations.ui.levels_page.stats')
        ->has('translations.ui.levels_page.mastery')
        ->has('translations.ui.levels_page.enter_level')
    );
});

test('levels page passes levels data loaded from database with null progress for guest', function () {
    $level = Level::factory()->create([
        'name' => '等級 1',
        'description' => '基礎入門：核心常用 1,000 單字',
    ]);

    $response = $this->get(route('levels.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Levels')
        ->has('levels', 1)
        ->where('levels.0.id', $level->id)
        ->where('levels.0.name', '等級 1')
        ->where('levels.0.description', '基礎入門：核心常用 1,000 單字')
        ->where('levels.0.vocabularies_count', 0)
        ->where('levels.0.groups_count', 0)
        ->where('progress', null)
    );
});

test('levels page calculates mastery percentage correctly based on 6 stages per group for authenticated user', function () {
    $user = User::factory()->create();

    $level = Level::factory()->create([
        'name' => '等級 1',
        'description' => '基礎入門：核心常用 1,000 單字',
    ]);

    // Create 4 groups for this level (4 groups * 6 stages = 24 max stages)
    $groups = Group::factory()->count(4)->create([
        'level_id' => $level->id,
    ]);

    // Group 1: Stage 6 (fully mastered)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 6,
    ]);

    // Group 2: Stage 6 (fully mastered) -> total 12 stages out of 24 = 50%
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[1]->id,
        'stage' => 6,
    ]);

    $response = $this->actingAs($user)->get(route('levels.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Levels')
        ->has('levels', 1)
        ->where('levels.0.id', $level->id)
        ->where('levels.0.groups_count', 4)
        ->where("progress.{$level->id}.total_stages", 12)
        ->where("progress.{$level->id}.max_stages", 24)
        ->where("progress.{$level->id}.mastery_percentage", 50)
    );
});

test('levels page calculates mastery percentage accurately for 100 groups standard level', function () {
    $user = User::factory()->create();

    $level = Level::factory()->create([
        'name' => '等級 1',
        'description' => '基礎入門：核心常用 1,000 單字',
    ]);

    // Create 100 groups (100 * 6 = 600 max stages)
    $groups = Group::factory()->count(100)->create([
        'level_id' => $level->id,
    ]);

    // User completed 10 groups to stage 6 (60 points) and 10 groups to stage 3 (30 points) = 90 / 600 = 15%
    for ($i = 0; $i < 10; $i++) {
        LearningProgress::factory()->create([
            'user_id' => $user->id,
            'level_id' => $level->id,
            'group_id' => $groups[$i]->id,
            'stage' => 6,
        ]);
    }

    for ($i = 10; $i < 20; $i++) {
        LearningProgress::factory()->create([
            'user_id' => $user->id,
            'level_id' => $level->id,
            'group_id' => $groups[$i]->id,
            'stage' => 3,
        ]);
    }

    $response = $this->actingAs($user)->get(route('levels.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Levels')
        ->has('levels', 1)
        ->where('levels.0.id', $level->id)
        ->where('levels.0.groups_count', 100)
        ->where("progress.{$level->id}.total_stages", 90)
        ->where("progress.{$level->id}.max_stages", 600)
        ->where("progress.{$level->id}.mastery_percentage", 15)
    );
});

test('levels page calculates decimal mastery percentage for early progress', function () {
    $user = User::factory()->create();

    $level = Level::factory()->create([
        'name' => '等級 1',
    ]);

    $groups = Group::factory()->count(100)->create([
        'level_id' => $level->id,
    ]);

    // User completed 1 stage of 1 group: 1 / 600 = 0.16666...% -> 0.17%
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 1,
    ]);

    $response = $this->actingAs($user)->get(route('levels.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Levels')
        ->where("progress.{$level->id}.total_stages", 1)
        ->where("progress.{$level->id}.max_stages", 600)
        ->where("progress.{$level->id}.mastery_percentage", 0.17)
    );
});
