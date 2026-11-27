<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\Level;
use App\Models\SentenceClozeQuestion;
use App\Models\User;
use App\Models\Vocabulary;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '等級 1', '基礎入門：核心常用 1,000 單字')
        ->create();
    Level::factory()->count(9)->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()
        ->count(10)
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ])
        ->each(function (Vocabulary $vocabulary) {
            SentenceClozeQuestion::factory()->create([
                'vocabulary_id' => $vocabulary->id,
            ]);
        });
});

test('guest can access welcome page and sees inertia welcome component', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Page')
    );
});

test('authenticated user is redirected from welcome page to dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});

test('unverified authenticated user is redirected from welcome page to dashboard', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});

test('welcome page shares ui translation keys for hero, features, and levels', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('translations.ui.welcome.hero.title')
        ->has('translations.ui.welcome.srs.title')
        ->has('translations.ui.welcome.features.srs_title')
        ->has('translations.ui.welcome.levels.title')
        ->has('translations.ui.welcome.levels.browse_all_title')
        ->has('translations.ui.sites.zh_TW')
    );
});

test('welcome page passes levels data loaded from database', function () {
    $level = Level::first();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Page')
        ->where('levels.0.id', $level->id)
        ->where('levels.0.name', '等級 1')
        ->where('levels.0.description', '基礎入門：核心常用 1,000 單字')
        ->where('levels.0.vocabularies_count', 10)
        ->where('levels.0.groups_count', 1)
    );
});

it('will only show 7 levels on welcome page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Page')
        ->has('levels', 7)
    );
});

test('welcome page passes a random vocabulary with localized translation and spelling test', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Page')
        ->has('showcase.vocabulary.id')
        ->has('showcase.vocabulary.word')
        ->has('showcase.vocabulary.part_of_speech')
        ->has('showcase.vocabulary.pronunciation')
        ->has('showcase.vocabulary.example_sentence')
        ->has('showcase.vocabulary.definition')
        ->has('showcase.vocabulary.example_translation')
        ->has('showcase.spelling_question.target_word')
        ->where('showcase.cloze_question.quiz_type', 'sentence_cloze')
        ->has('showcase.cloze_question.options', 4)
        ->has('showcase.cloze_question.sentence_segments', 3)
        ->where('showcase.spelling_question.quiz_type', 'spelling')
        ->has('showcase.spelling_question.target_word')
    );
});
