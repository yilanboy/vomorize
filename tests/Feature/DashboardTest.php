<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('dashboard shares ui translation keys for dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('translations.ui.dashboard.title')
        ->has('translations.ui.dashboard.kpis.streak')
        ->has('translations.ui.dashboard.action_center.title')
        ->has('translations.ui.dashboard.srs.title')
        ->has('translations.ui.dashboard.levels.title')
    );
});

test('new authenticated user sees dashboard in empty state with default stats', function () {
    $level1 = Level::factory()->create(['name' => '等級 1']);
    $group1 = Group::factory()->create([
        'level_id' => $level1->id,
        'sequence' => 1,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('stats.is_empty_state', true)
        ->where('stats.kpis.streak.current', 0)
        ->where('stats.kpis.streak.longest', 0)
        ->where('stats.kpis.streak.is_checked_in_today', false)
        ->where('stats.kpis.learning_words.words_count', 0)
        ->where('stats.kpis.mastered_words.words_count', 0)
        ->where('stats.kpis.due_groups.count', 0)
        ->where('stats.kpis.due_groups.has_due', false)
        ->where('stats.action_center.due_reviews', [])
        ->where('stats.action_center.upcoming_reviews', [])
        ->where('stats.action_center.recommended_next_group.group_id', $group1->id)
        ->where('stats.srs_distribution.has_data', false)
        ->has('stats.level_progress.levels')
    );
});

test('active authenticated user receives correctly calculated streak and kpis', function () {
    $level = Level::factory()->create(['name' => '等級 1']);
    $groups = Group::factory()->count(5)->sequence(
        ['level_id' => $level->id, 'sequence' => 1],
        ['level_id' => $level->id, 'sequence' => 2],
        ['level_id' => $level->id, 'sequence' => 3],
        ['level_id' => $level->id, 'sequence' => 4],
        ['level_id' => $level->id, 'sequence' => 5],
    )->create();

    $user = User::factory()->create([
        'current_streak' => 5,
        'longest_streak' => 10,
        'last_studied_date' => now(),
    ]);

    // Group 1: Stage 2 (Learning)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 2,
        'last_score' => 90,
        'next_review_at' => now()->addDays(2),
    ]);

    // Group 2: Stage 3 (Learning, Due)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[1]->id,
        'stage' => 3,
        'last_score' => 85,
        'next_review_at' => now()->subMinutes(10),
    ]);

    // Group 3: Stage 6 (Mastered)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[2]->id,
        'stage' => 6,
        'last_score' => 100,
        'next_review_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('stats.is_empty_state', false)
        ->where('stats.kpis.streak.current', 5)
        ->where('stats.kpis.streak.longest', 10)
        ->where('stats.kpis.streak.is_checked_in_today', true)
        ->where('stats.kpis.learning_words.groups_count', 2)
        ->where('stats.kpis.learning_words.words_count', 20)
        ->where('stats.kpis.mastered_words.groups_count', 1)
        ->where('stats.kpis.mastered_words.words_count', 10)
        ->where('stats.kpis.due_groups.count', 1)
        ->where('stats.kpis.due_groups.has_due', true)
    );
});

test('action center provides ordered due reviews, upcoming cooldowns, and next recommendation', function () {
    $level = Level::factory()->create(['name' => '等級 1']);
    $groups = Group::factory()->count(5)->sequence(
        ['level_id' => $level->id, 'sequence' => 1],
        ['level_id' => $level->id, 'sequence' => 2],
        ['level_id' => $level->id, 'sequence' => 3],
        ['level_id' => $level->id, 'sequence' => 4],
        ['level_id' => $level->id, 'sequence' => 5],
    )->create();

    $user = User::factory()->create();

    // Group 1: Stage 1, Failed test (score 70)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 1,
        'last_score' => 70,
        'next_review_at' => now()->addDay(), // even if future, failed means immediate retest!
    ]);

    // Group 2: Stage 3, Cooldown finished 5 mins ago, score 90
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[1]->id,
        'stage' => 3,
        'last_score' => 90,
        'next_review_at' => now()->subMinutes(5),
    ]);

    // Group 3: Stage 5, Cooldown finished 20 mins ago (target stage 6 mastery)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[2]->id,
        'stage' => 5,
        'last_score' => 95,
        'next_review_at' => now()->subMinutes(20),
    ]);

    // Group 4: Stage 2, In cooldown (next review in 2 hours)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[3]->id,
        'stage' => 2,
        'last_score' => 85,
        'next_review_at' => now()->addHours(2),
    ]);

    // Group 5 is not started

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        // due_reviews count is 3
        ->has('stats.action_center.due_reviews', 3)
        // Item 0: Failed Group 1 (priority)
        ->where('stats.action_center.due_reviews.0.group_id', $groups[0]->id)
        ->where('stats.action_center.due_reviews.0.badge_text', '重測 Stage 1')
        ->where('stats.action_center.due_reviews.0.badge_variant', 'warning')
        ->where('stats.action_center.due_reviews.0.last_score', 70)
        ->where('stats.action_center.due_reviews.0.status_hint', '上次得分：70 分 (未及格)')
        // Item 1: Group 3 (finished cooldown 20 min ago, earlier than Group 2)
        ->where('stats.action_center.due_reviews.1.group_id', $groups[2]->id)
        ->where('stats.action_center.due_reviews.1.badge_text', '終極驗收 Stage 6')
        ->where('stats.action_center.due_reviews.1.badge_variant', 'success')
        ->where('stats.action_center.due_reviews.1.status_hint', '即將達成永久記憶')
        // Item 2: Group 2 (finished cooldown 5 min ago)
        ->where('stats.action_center.due_reviews.2.group_id', $groups[1]->id)
        ->where('stats.action_center.due_reviews.2.badge_text', '挑戰 Stage 4')
        ->where('stats.action_center.due_reviews.2.badge_variant', 'primary')
        ->where('stats.action_center.due_reviews.2.status_hint', '冷卻完畢')
        // Upcoming reviews has Group 4
        ->has('stats.action_center.upcoming_reviews', 1)
        ->where('stats.action_center.upcoming_reviews.0.group_id', $groups[3]->id)
        ->where('stats.action_center.upcoming_reviews.0.stage_label', '進階 Stage 3')
        // Recommended next group is Group 5
        ->where('stats.action_center.recommended_next_group.group_id', $groups[4]->id)
        ->where('stats.action_center.recommended_next_group.start_url', "/groups/{$groups[4]->id}")
    );
});

test('srs stage breakdown and level progress are accurately categorized and calculated', function () {
    $level = Level::factory()->create(['name' => '等級 1']);
    $groups = Group::factory()->count(4)->sequence(
        ['level_id' => $level->id, 'sequence' => 1],
        ['level_id' => $level->id, 'sequence' => 2],
        ['level_id' => $level->id, 'sequence' => 3],
        ['level_id' => $level->id, 'sequence' => 4],
    )->create();

    $user = User::factory()->create();

    // Group 1: Stage 2 but Failed (score 75) -> classified as stage_0 (待重測)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 2,
        'last_score' => 75,
    ]);

    // Group 2: Stage 1, passed -> stage_1_2 (初識記憶)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[1]->id,
        'stage' => 1,
        'last_score' => 85,
    ]);

    // Group 3: Stage 4, passed -> stage_3_4 (短期鞏固)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[2]->id,
        'stage' => 4,
        'last_score' => 90,
    ]);

    // Group 4: Stage 6 -> stage_6 (永久精通)
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[3]->id,
        'stage' => 6,
        'last_score' => 100,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('stats.srs_distribution.total_contacted_groups', 4)
        ->where('stats.srs_distribution.total_contacted_words', 40)
        ->where('stats.srs_distribution.has_data', true)
        // Stage 0: 1 group (25%)
        ->where('stats.srs_distribution.stages.0.key', 'stage_0')
        ->where('stats.srs_distribution.stages.0.label', '待重測')
        ->where('stats.srs_distribution.stages.0.description', '未及格待複習')
        ->where('stats.srs_distribution.stages.0.groups_count', 1)
        ->where('stats.srs_distribution.stages.0.percentage', 25)
        // Stage 1~2: 1 group (25%)
        ->where('stats.srs_distribution.stages.1.key', 'stage_1_2')
        ->where('stats.srs_distribution.stages.1.label', '初識記憶')
        ->where('stats.srs_distribution.stages.1.description', '短週期強化中')
        ->where('stats.srs_distribution.stages.1.groups_count', 1)
        ->where('stats.srs_distribution.stages.1.percentage', 25)
        // Stage 3~4: 1 group (25%)
        ->where('stats.srs_distribution.stages.2.key', 'stage_3_4')
        ->where('stats.srs_distribution.stages.2.groups_count', 1)
        ->where('stats.srs_distribution.stages.2.percentage', 25)
        // Stage 5: 0 group (0%)
        ->where('stats.srs_distribution.stages.3.key', 'stage_5')
        ->where('stats.srs_distribution.stages.3.groups_count', 0)
        ->where('stats.srs_distribution.stages.3.percentage', 0)
        // Stage 6: 1 group (25%)
        ->where('stats.srs_distribution.stages.4.key', 'stage_6')
        ->where('stats.srs_distribution.stages.4.label', '永久精通')
        ->where('stats.srs_distribution.stages.4.description', '永久記憶固化')
        ->where('stats.srs_distribution.stages.4.groups_count', 1)
        ->where('stats.srs_distribution.stages.4.percentage', 25)
        // Level 1 progress
        ->where('stats.level_progress.levels.0.id', $level->id)
        ->where('stats.level_progress.levels.0.subtitle', '基礎入門')
        ->where('stats.level_progress.levels.0.mastered_groups_count', 1)
        ->where('stats.level_progress.levels.0.learning_groups_count', 3)
        ->where('stats.level_progress.levels.0.mastery_percentage', 54.17) // (2 + 1 + 4 + 6) / 24 = 13/24 = 54.17%
    );
});
