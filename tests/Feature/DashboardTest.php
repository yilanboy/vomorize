<?php

declare(strict_types=1);

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
        ->missing('translations.ui.dashboard.srs')
        ->missing('translations.ui.dashboard.levels')
    );
});

test('new authenticated user sees dashboard in empty state with streamlined stats', function () {
    $level1 = Level::factory()->withTranslation('zh_TW', '等級 1')->create();
    $group1 = Group::factory()->create([
        'level_id' => $level1->id,
        'sequence' => 1,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard/Page')
        ->where('stats.is_empty_state', true)
        ->where('stats.kpis.streak.current', 0)
        ->where('stats.kpis.streak.longest', 0)
        ->where('stats.kpis.streak.is_checked_in_today', false)
        ->where('stats.kpis.learning_words.words_count', 0)
        ->where('stats.kpis.learning_words.groups_count', 0)
        ->where('stats.kpis.mastered_words.words_count', 0)
        ->where('stats.kpis.mastered_words.groups_count', 0)
        ->where('stats.action_center.due_reviews', [])
        ->where('stats.action_center.recommended_next_group.group_id', $group1->id)
        ->where('stats.action_center.recommended_next_group.start_url', "/groups/{$group1->id}/intro")
        ->missing('stats.kpis.due_groups')
        ->missing('stats.kpis.mastered_words.percentage')
        ->missing('stats.action_center.upcoming_reviews')
        ->missing('stats.srs_distribution')
        ->missing('stats.level_progress')
    );
});

test('active authenticated user receives correctly calculated streak and kpis', function () {
    $level = Level::factory()->withTranslation('zh_TW', '等級 1')->create();
    $groups = Group::factory()->count(4)->sequence(
        ['level_id' => $level->id, 'sequence' => 1],
        ['level_id' => $level->id, 'sequence' => 2],
        ['level_id' => $level->id, 'sequence' => 3],
        ['level_id' => $level->id, 'sequence' => 4],
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
        ->component('dashboard/Page')
        ->where('stats.is_empty_state', false)
        ->where('stats.kpis.streak.current', 5)
        ->where('stats.kpis.streak.longest', 10)
        ->where('stats.kpis.streak.is_checked_in_today', true)
        ->where('stats.kpis.learning_words.groups_count', 2)
        ->where('stats.kpis.learning_words.words_count', 20)
        ->where('stats.kpis.mastered_words.groups_count', 1)
        ->where('stats.kpis.mastered_words.words_count', 10)
    );
});

test('action center provides ordered due reviews and next recommendation without cooldown noise', function () {
    $level = Level::factory()->withTranslation('zh_TW', '等級 1')->create();
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

    // Group 4: Stage 2, In cooldown (next review in 2 hours) -> omitted from dashboard
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
        ->component('dashboard/Page')
        ->has('stats.action_center.due_reviews', 3)
        // Item 0: Failed Group 1 (priority)
        ->where('stats.action_center.due_reviews.0.group_id', $groups[0]->id)
        ->where('stats.action_center.due_reviews.0.badge_text', '重測 Stage 1')
        ->where('stats.action_center.due_reviews.0.badge_variant', 'warning')
        ->where('stats.action_center.due_reviews.0.is_failed', true)
        ->where('stats.action_center.due_reviews.0.last_score', 70)
        ->where('stats.action_center.due_reviews.0.review_url', "/groups/{$groups[0]->id}/quiz")
        // Item 1: Group 3 (finished cooldown 20 min ago, earlier than Group 2)
        ->where('stats.action_center.due_reviews.1.group_id', $groups[2]->id)
        ->where('stats.action_center.due_reviews.1.badge_text', '終極驗收 Stage 6')
        ->where('stats.action_center.due_reviews.1.badge_variant', 'success')
        ->where('stats.action_center.due_reviews.1.is_failed', false)
        ->where('stats.action_center.due_reviews.1.review_url', "/groups/{$groups[2]->id}/quiz")
        // Item 2: Group 2 (finished cooldown 5 min ago)
        ->where('stats.action_center.due_reviews.2.group_id', $groups[1]->id)
        ->where('stats.action_center.due_reviews.2.badge_text', '挑戰 Stage 4')
        ->where('stats.action_center.due_reviews.2.badge_variant', 'primary')
        ->where('stats.action_center.due_reviews.2.is_failed', false)
        // Recommended next group is Group 5
        ->where('stats.action_center.recommended_next_group.group_id', $groups[4]->id)
        ->where('stats.action_center.recommended_next_group.start_url', "/groups/{$groups[4]->id}/intro")
        ->missing('stats.action_center.upcoming_reviews')
    );
});
