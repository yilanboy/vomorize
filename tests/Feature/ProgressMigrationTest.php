<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;

beforeEach(function () {
    $this->level = Level::create([
        'id' => 1,
    ]);

    $this->group1 = Group::create(['id' => 1, 'level_id' => 1, 'sequence' => 1]);
    $this->group2 = Group::create(['id' => 2, 'level_id' => 1, 'sequence' => 2]);
});

it('migrates guest progress into newly signed-in user account', function () {
    $user = User::factory()->create();

    $guestData = [
        [
            'group_id' => 1,
            'stage' => 2,
            'last_score' => 90,
            'last_reviewed_at' => now()->toIso8601String(),
            'next_review_at' => now()->addDays(1)->toIso8601String(),
        ],
    ];

    $response = $this->actingAs($user)->postJson('/progress/migrate', [
        'guest_progress' => $guestData,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'migrated_count' => 1,
        ]);

    $this->assertDatabaseHas('learning_progress', [
        'user_id' => $user->id,
        'group_id' => 1,
        'stage' => 2,
        'last_score' => 90,
    ]);
});

it('resolves migration conflicts by keeping more advanced progress', function () {
    $user = User::factory()->create();

    // Existing server progress: stage 1, score 70
    LearningProgress::create([
        'user_id' => $user->id,
        'group_id' => 1,
        'stage' => 1,
        'last_score' => 70,
        'last_reviewed_at' => now()->subDay(),
        'next_review_at' => now()->addHours(12),
    ]);

    // Guest progress: stage 2, score 85 (more advanced)
    $guestData = [
        [
            'group_id' => 1,
            'stage' => 2,
            'last_score' => 85,
            'last_reviewed_at' => now()->toIso8601String(),
            'next_review_at' => now()->addDays(1)->toIso8601String(),
        ],
    ];

    $response = $this->actingAs($user)->postJson('/progress/migrate', [
        'guest_progress' => $guestData,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'migrated_count' => 1,
        ]);

    $this->assertDatabaseHas('learning_progress', [
        'user_id' => $user->id,
        'group_id' => 1,
        'stage' => 2,
        'last_score' => 85,
    ]);
});

it('keeps a migrated learner on the review phase after an earlier device left a stage zero record', function () {
    for ($i = 1; $i <= 3; $i++) {
        $vocabulary = Vocabulary::create([
            'group_id' => $this->group1->id,
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

    $user = User::factory()->create();

    // First device: the guest failed the introduction, so it recorded a stage zero attempt.
    $this->actingAs($user)->postJson('/progress/migrate', [
        'guest_progress' => [
            [
                'group_id' => $this->group1->id,
                'stage' => 0,
                'last_score' => 50,
                'last_reviewed_at' => now()->subWeek()->toIso8601String(),
                'next_review_at' => now()->subWeek()->addHours(12)->toIso8601String(),
            ],
        ],
    ])->assertOk();

    // Second device: the guest passed the introduction and reached stage three.
    $this->actingAs($user)->postJson('/progress/migrate', [
        'guest_progress' => [
            [
                'group_id' => $this->group1->id,
                'stage' => 3,
                'last_score' => 100,
                'last_reviewed_at' => now()->subDays(3)->toIso8601String(),
                'next_review_at' => now()->subDay()->toIso8601String(),
            ],
        ],
    ])->assertOk();

    $this->actingAs($user)
        ->get("/zh-tw/groups/{$this->group1->id}/quiz")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('groups/Quiz'));
});
