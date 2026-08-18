<?php

use App\Models\Group;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;

beforeEach(function () {
    $this->level = Level::create([
        'id' => 1,
    ]);

    $this->group = Group::create([
        'id' => 1,
        'level_id' => $this->level->id,
        'sequence' => 1,
    ]);

    for ($i = 1; $i <= 10; $i++) {
        $vocab = Vocabulary::create([
            'id' => $i,
            'group_id' => $this->group->id,
            'word' => "word_{$i}",
            'part_of_speech' => 'n.',
            'pronunciation' => '/test/',
            'example_sentence' => "Example sentence {$i}.",
        ]);

        VocabularyTranslation::create([
            'vocabulary_id' => $vocab->id,
            'locale' => 'zh-tw',
            'definition' => "釋義 {$i}",
            'example_translation' => "例句翻譯 {$i}",
        ]);
    }
});

it('renders home page with level payload', function () {
    $response = $this->get('/zh-tw');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('levels', 1)
        );
});

it('renders level page with groups payload', function () {
    $response = $this->get("/zh-tw/levels/{$this->level->id}");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('level/Show')
            ->where('level.id', $this->level->id)
            ->has('groups', 1)
        );
});

it('renders group overview page with vocabulary and audio URLs', function () {
    $response = $this->get("/zh-tw/groups/{$this->group->id}");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('groups/Show')
            ->where('group.id', $this->group->id)
            ->has('vocabularies', 10)
            ->where('vocabularies.0.audio_url', 'https://assets.vomorize.com/vocabulary/1/word.mp3')
            ->where('vocabularies.0.sentence_audio_url', 'https://assets.vomorize.com/vocabulary/1/sentence.mp3')
        );
});

it('carries every locale of vocabulary content into the group overview', function () {
    VocabularyTranslation::create([
        'vocabulary_id' => 1,
        'locale' => 'ja',
        'definition' => '釈義 1',
        'example_translation' => 'ja 例句 1',
    ]);

    $this->get("/zh-tw/groups/{$this->group->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('vocabularies.0.translations.zh-tw.definition', '釋義 1')
            ->where('vocabularies.0.translations.ja.definition', '釈義 1')
            ->missing('vocabularies.0.definition')
            ->missing('vocabularies.0.is_fallback')
        );
});

it('updates learning progress on score submission for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson("/zh-tw/groups/{$this->group->id}/progress", [
        'phase' => 'quiz',
        'score' => 90,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'progress' => [
                'stage' => 1,
                'last_score' => 90,
            ],
        ]);

    $this->assertDatabaseHas('learning_progress', [
        'user_id' => $user->id,
        'group_id' => $this->group->id,
        'stage' => 1,
        'last_score' => 90,
    ]);
});
