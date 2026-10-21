<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use Inertia\Testing\AssertableInertia as Assert;

test('guest can access group detail page and sees inertia groups/Show component with null progress', function () {
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

    $response = $this->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('groups/Show')
        ->has('group')
        ->where('group.id', $group->id)
        ->where('group.sequence', 1)
        ->where('group.level_id', $level->id)
        ->where('group.level_name', '基礎入門')
        ->where('group.vocabularies_count', 10)
        ->where('progress', null)
        ->has('vocabularies', 10)
    );
});

test('group detail page returns 404 for non-existent group', function () {
    $response = $this->get('/groups/99999');

    $response->assertNotFound();
});

test('group detail page includes correctly structured vocabulary data with CDN audio URLs', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $vocab = Vocabulary::factory()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'word' => 'abandon',
        'part_of_speech' => 'v.',
        'pronunciation' => '/əˈbændən/',
        'definition' => '放棄；拋棄',
        'example_sentence' => 'They had to abandon the plan.',
        'example_translation' => '他們不得不放棄這個計劃。',
    ]);

    $response = $this->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('groups/Show')
        ->where('vocabularies.0.id', $vocab->id)
        ->where('vocabularies.0.word', 'abandon')
        ->where('vocabularies.0.part_of_speech', 'v.')
        ->where('vocabularies.0.pronunciation', '/əˈbændən/')
        ->where('vocabularies.0.definition', '放棄；拋棄')
        ->where('vocabularies.0.example_sentence', 'They had to abandon the plan.')
        ->where('vocabularies.0.example_translation', '他們不得不放棄這個計劃。')
        ->where('vocabularies.0.word_audio_url', "https://assets.vomorize.com/vocabulary/{$vocab->id}/word.mp3")
        ->where('vocabularies.0.sentence_audio_url', "https://assets.vomorize.com/vocabulary/{$vocab->id}/sentence.mp3")
    );
});

test('group detail page calculates learning status for authenticated user correctly', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    // 0. Authenticated user without progress starts at not_started and stage 0
    $response = $this->actingAs($user)->get(route('groups.show', $group));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('groups/Show')
        ->where('progress.status', 'not_started')
        ->where('progress.stage', 0)
        ->where('progress.cooldown_remaining_seconds', null)
    );

    // 1. In Cooldown
    $futureReviewAt = now()->addHours(3);
    $progress = LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 2,
        'next_review_at' => $futureReviewAt,
    ]);

    $response = $this->actingAs($user)->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('groups/Show')
        ->where('progress.status', 'cooldown')
        ->where('progress.stage', 2)
        ->has('progress.cooldown_remaining_seconds')
    );

    // 2. Ready (next_review_at is in the past)
    $progress->update([
        'next_review_at' => now()->subMinutes(10),
    ]);

    $response = $this->actingAs($user)->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('groups/Show')
        ->where('progress.status', 'ready')
        ->where('progress.stage', 2)
        ->where('progress.cooldown_remaining_seconds', null)
    );

    // 3. Completed (stage 6)
    $progress->update([
        'stage' => 6,
        'next_review_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('groups/Show')
        ->where('progress.status', 'completed')
        ->where('progress.stage', 6)
        ->where('progress.cooldown_remaining_seconds', null)
    );
});

test('group detail page shares ui translation keys for group_detail', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $response = $this->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('translations.ui.group_detail.title')
        ->has('translations.ui.group_detail.breadcrumb_levels')
        ->has('translations.ui.group_detail.breadcrumb_level')
        ->has('translations.ui.group_detail.breadcrumb_group')
        ->has('translations.ui.group_detail.status.ready')
        ->has('translations.ui.group_detail.status.cooldown')
        ->has('translations.ui.group_detail.status.not_started')
        ->has('translations.ui.group_detail.status.completed')
        ->has('translations.ui.group_detail.play_word_audio')
        ->has('translations.ui.group_detail.play_sentence_audio')
        ->has('translations.ui.group_detail.sticky_bar.summary')
        ->has('translations.ui.group_detail.sticky_bar.start_stage_1')
        ->has('translations.ui.group_detail.sticky_bar.start_review')
        ->has('translations.ui.group_detail.sticky_bar.cooldown')
        ->has('translations.ui.group_detail.sticky_bar.mastered')
        ->has('translations.ui.time.days')
        ->has('translations.ui.time.hours')
        ->has('translations.ui.time.minutes')
        ->has('translations.ui.time.seconds')
    );
});
