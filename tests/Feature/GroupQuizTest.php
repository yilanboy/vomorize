<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->level = Level::create(['id' => 1]);
    $this->group = Group::create([
        'id' => 1,
        'level_id' => $this->level->id,
        'sequence' => 1,
    ]);

    for ($i = 1; $i <= 4; $i++) {
        $vocabulary = Vocabulary::create([
            'id' => $i,
            'group_id' => $this->group->id,
            'word' => "word_{$i}",
            'part_of_speech' => 'n.',
            'pronunciation' => '/test/',
            'example_sentence' => "Example sentence {$i}.",
        ]);

        VocabularyTranslation::create([
            'vocabulary_id' => $vocabulary->id,
            'locale' => 'zh_TW',
            'definition' => "釋義 {$i}",
            'example_translation' => "例句翻譯 {$i}",
        ]);
    }
});

/**
 * Which locale to display is now the client's decision, so both phases carry every locale's
 * content rather than the one this request happened to resolve to. That is what lets a learner
 * change language mid-phase without the question set being rebuilt underneath them.
 */
it('carries every locale of vocabulary content into both quiz phases', function (string $route) {
    foreach (['zh_CN' => '释义', 'ja' => '釈義'] as $locale => $noun) {
        for ($i = 1; $i <= 4; $i++) {
            VocabularyTranslation::create([
                'vocabulary_id' => $i,
                'locale' => $locale,
                'definition' => "{$noun} {$i}",
                'example_translation' => "{$locale} 例句 {$i}",
            ]);
        }
    }

    $this->get("/zh-tw/groups/{$this->group->id}{$route}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('vocabularies.0.translations.zh_TW.definition', '釋義 1')
            ->where('vocabularies.0.translations.zh_CN.definition', '释义 1')
            ->where('vocabularies.0.translations.ja.definition', '釈義 1')
            ->where('vocabularies.0.translations.ja.example_translation', 'ja 例句 1')
            ->missing('vocabularies.0.definition')
            ->missing('vocabularies.0.example_translation')
            ->missing('vocabularies.0.is_fallback')
        );
})->with([
    'introduction' => ['/introduce'],
    'review' => ['/quiz'],
]);

it('renders the introduction for a group without completed introduction', function () {
    $response = $this->get("/zh-tw/groups/{$this->group->id}/introduce");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('groups/Introduce')
            ->where('group.id', $this->group->id)
            ->has('vocabularies', 4)
        );
});

it('redirects a user with a completed introduction away from the introduction route', function () {
    $user = User::factory()->create();

    LearningProgress::create([
        'user_id' => $user->id,
        'group_id' => $this->group->id,
        'stage' => 1,
    ]);

    $this->actingAs($user)
        ->get("/zh-tw/groups/{$this->group->id}/introduce")
        ->assertRedirect("/zh-tw/groups/{$this->group->id}/quiz");
});

it('redirects a user without a completed introduction away from the review route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get("/zh-tw/groups/{$this->group->id}/quiz")
        ->assertRedirect("/zh-tw/groups/{$this->group->id}/introduce");
});

it('marks the introduction complete and advances progress when its score passes', function () {
    Carbon::setTestNow('2026-07-28 12:00:00');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson("/zh-tw/groups/{$this->group->id}/progress", [
        'phase' => 'introduce',
        'score' => 90,
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('learning_progress', [
        'user_id' => $user->id,
        'group_id' => $this->group->id,
        'stage' => 1,
        'last_score' => 90,
    ]);

    expect(LearningProgress::first()->last_reviewed_at->toIso8601String())
        ->toBe('2026-07-28T12:00:00+00:00');
});

it('does not persist a failed introduction attempt', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson("/zh-tw/groups/{$this->group->id}/progress", [
        'phase' => 'introduce',
        'score' => 89,
    ])->assertOk();

    $this->assertDatabaseMissing('learning_progress', [
        'user_id' => $user->id,
        'group_id' => $this->group->id,
    ]);
});

it('applies a one day pending period after a failed review', function () {
    Carbon::setTestNow('2026-07-28 12:00:00');
    $user = User::factory()->create();

    LearningProgress::create([
        'user_id' => $user->id,
        'group_id' => $this->group->id,
        'stage' => 2,
    ]);

    $this->actingAs($user)->postJson("/zh-tw/groups/{$this->group->id}/progress", [
        'phase' => 'quiz',
        'score' => 89,
    ])->assertOk();

    expect(LearningProgress::first()->next_review_at->toIso8601String())
        ->toBe('2026-07-29T12:00:00+00:00');
});

it('renders a result page with the submitted session result', function () {
    $response = $this->get("/zh-tw/groups/{$this->group->id}/result?score=90&phase=quiz");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('groups/Result')
            ->where('result.score', 90)
            ->where('result.phase', 'quiz')
        );
});
test('example', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
});

it('lets a guest through to the review phase, since only their browser knows their progress', function () {
    $this->get("/zh-tw/groups/{$this->group->id}/quiz")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('groups/Quiz')
            ->where('progress', null)
        );
});

it('holds a member out of the review phase until the cooldown elapses', function () {
    Carbon::setTestNow('2026-07-28 12:00:00');
    $user = User::factory()->create();

    LearningProgress::create([
        'user_id' => $user->id,
        'group_id' => $this->group->id,
        'stage' => 1,
        'last_score' => 100,
        'last_reviewed_at' => now(),
        'next_review_at' => now()->addHours(12),
    ]);

    $this->actingAs($user)
        ->get("/zh-tw/groups/{$this->group->id}/quiz")
        ->assertRedirect("/zh-tw/groups/{$this->group->id}");

    $this->travel(13)->hours();

    $this->actingAs($user)
        ->get("/zh-tw/groups/{$this->group->id}/quiz")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('groups/Quiz'));
});

it('advances a member one stage per passing review until the group is complete', function () {
    Carbon::setTestNow('2026-07-28 12:00:00');
    $user = User::factory()->create();

    $this->actingAs($user)->postJson("/zh-tw/groups/{$this->group->id}/progress", [
        'phase' => 'introduce',
        'score' => 100,
    ])->assertOk()->assertJsonPath('progress.stage', 1);

    // Each stage waits longer than the last before its review unlocks: 12h, 1d, 2d, 4d, 7d.
    foreach ([12, 24, 48, 96, 168] as $index => $cooldownHours) {
        $this->travel($cooldownHours + 1)->hours();

        $this->actingAs($user)->postJson("/zh-tw/groups/{$this->group->id}/progress", [
            'phase' => 'quiz',
            'score' => 100,
        ])->assertOk()->assertJsonPath('progress.stage', $index + 2);
    }

    $this->actingAs($user)
        ->get("/zh-tw/groups/{$this->group->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('progress.stage', 6)
            ->where('progress.status', 'completed')
            ->where('progress.next_review_at', null)
        );
});
