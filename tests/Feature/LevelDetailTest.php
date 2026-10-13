<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guest can access level detail page and sees inertia levels/Show component', function () {
    $level = Level::factory()->create([
        'name' => '基礎入門',
        'description' => '核心常用 1,000 單字',
    ]);

    Group::factory()->count(10)->create([
        'level_id' => $level->id,
    ]);

    $response = $this->get(route('levels.show', $level));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('levels/Show')
        ->has('level')
        ->where('level.id', $level->id)
        ->where('level.name', '基礎入門')
        ->where('level.groups_count', 10)
        ->has('groups', 10)
        ->where('groups.0.sequence', 1)
        ->where('groups.0.status', 'ready')
        ->where('groups.1.status', 'not_started')
        ->where('counts.all', 10)
        ->where('counts.ready', 1)
        ->where('counts.not_started', 9)
        ->where('counts.cooldown', 0)
        ->where('counts.completed', 0)
    );
});

test('level detail page returns 404 for non-existent level', function () {
    $response = $this->get('/levels/999');

    $response->assertNotFound();
});

test('authenticated user can access level detail page', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create([
        'name' => '初級進階',
    ]);

    $response = $this->actingAs($user)->get(route('levels.show', $level));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('levels/Show')
    );
});

test('level detail page shares ui translation keys for level_detail', function () {
    $level = Level::factory()->create();

    $response = $this->get(route('levels.show', $level));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('translations.ui.level_detail.title')
        ->has('translations.ui.level_detail.subtitle')
        ->has('translations.ui.level_detail.breadcrumb_levels')
        ->has('translations.ui.level_detail.breadcrumb_level')
        ->has('translations.ui.level_detail.filters.all')
        ->has('translations.ui.level_detail.filters.ready')
        ->has('translations.ui.level_detail.filters.cooldown')
        ->has('translations.ui.level_detail.filters.not_started')
        ->has('translations.ui.level_detail.filters.completed')
        ->has('translations.ui.level_detail.card_aria')
        ->has('translations.ui.level_detail.footer_hint')
        ->has('translations.ui.level_detail.footer_stats')
    );
});

test('calculates group learning statuses and counts correctly for authenticated user', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create([
        'name' => '基礎入門',
    ]);

    // Create 4 groups for this level
    $groups = Group::factory()->count(4)->create([
        'level_id' => $level->id,
    ]);

    // Group 1: Completed (stage 6)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 6,
    ]);

    // Group 2: Ready for review (stage 2, next_review_at in the past)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[1]->id,
        'stage' => 2,
        'next_review_at' => now()->subHours(2),
    ]);

    // Group 3: In Cooldown (stage 3, next_review_at in the future)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[2]->id,
        'stage' => 3,
        'next_review_at' => now()->addDays(2),
    ]);

    // Group 4: Unstarted (no learning progress record)

    $response = $this->actingAs($user)->get(route('levels.show', $level));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('levels/Show')
        ->where('counts.all', 4)
        ->where('counts.completed', 1)
        ->where('counts.ready', 1)
        ->where('counts.cooldown', 1)
        ->where('counts.not_started', 1)
        ->where('level.total_stages', 11) // 6 + 2 + 3 = 11
        ->where('level.max_stages', 24)   // 4 * 6 = 24
        ->where('level.mastery_percentage', 46) // round((11/24)*100) = 46%
        ->where('groups.0.status', 'completed')
        ->where('groups.1.status', 'ready')
        ->where('groups.2.status', 'cooldown')
        ->where('groups.3.status', 'not_started')
    );
});

test('first unstarted group becomes ready when no reviews are due', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();

    $groups = Group::factory()->count(3)->create([
        'level_id' => $level->id,
    ]);

    // Group 1: In Cooldown (stage 1, next_review_at in future)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 1,
        'next_review_at' => now()->addHours(4),
    ]);

    // Group 2 & 3: Unstarted
    // Since group 1 is in cooldown and no other review is due, Group 2 should be marked 'ready'
    $response = $this->actingAs($user)->get(route('levels.show', $level));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('levels/Show')
        ->where('counts.cooldown', 1)
        ->where('counts.ready', 1)
        ->where('counts.not_started', 1)
        ->where('groups.0.status', 'cooldown')
        ->where('groups.1.status', 'ready')
        ->where('groups.2.status', 'not_started')
    );
});
