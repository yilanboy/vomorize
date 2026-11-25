<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\Level;
use App\Models\SentenceClozeQuestion;
use App\Models\User;
use App\Models\Vocabulary;
use Inertia\Testing\AssertableInertia as Assert;

test('guest can access welcome page and sees inertia welcome component', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Page')
    );
});

test('authenticated user is redirected from welcome page to dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});

test('unverified authenticated user is redirected from welcome page to dashboard', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('home'));

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
    $level = Level::factory()
        ->withTranslation('zh_TW', '等級 1', '基礎入門：核心常用 1,000 單字')
        ->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Page')
        ->has('levels', 1)
        ->where('levels.0.id', $level->id)
        ->where('levels.0.name', '等級 1')
        ->where('levels.0.description', '基礎入門：核心常用 1,000 單字')
        ->where('levels.0.vocabularies_count', 0)
        ->where('levels.0.groups_count', 0)
    );
});

it('will only show 7 levels on welcome page', function () {
    Level::factory()->count(10)->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Page')
        ->has('levels', 7)
    );
});

test('welcome page passes a random vocabulary with localized translation', function () {
    $vocabulary = Vocabulary::factory()
        ->withTranslation('zh_TW', '靜謐的', '湖面在黎明時分十分靜謐。')
        ->create([
            'word' => 'serene',
            'part_of_speech' => 'adj.',
            'pronunciation' => '/səˈriːn/',
            'example_sentence' => 'The lake was serene at dawn.',
        ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Page')
        ->where('vocabulary.id', $vocabulary->id)
        ->where('vocabulary.word', 'serene')
        ->where('vocabulary.part_of_speech', 'adj.')
        ->where('vocabulary.pronunciation', '/səˈriːn/')
        ->where('vocabulary.example_sentence', 'The lake was serene at dawn.')
        ->where('vocabulary.translation.definition', '靜謐的')
        ->where('vocabulary.translation.example_translation', '湖面在黎明時分十分靜謐。')
        ->where('showcase.vocabulary.id', $vocabulary->id)
        ->where('showcase.spelling_question.target_word', 'serene')
    );
});

test('welcome page builds real cloze and spelling showcase when group has sentence cloze data', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $vocabularies = Vocabulary::factory()
        ->count(4)
        ->withTranslation('zh_TW', '韌性', '她的韌性幫助她很快從挫折中重新振作。')
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

    $target = $vocabularies->firstOrFail();

    SentenceClozeQuestion::factory()->create([
        'vocabulary_id' => $target->id,
        'sentence' => 'Her {{blank}} helped her recover quickly from the setback.',
        'blank_word' => $target->word,
        'distractors' => $vocabularies
            ->slice(1)
            ->values()
            ->map(static fn (Vocabulary $v): array => ['vocabulary_id' => $v->id, 'word' => $v->word])
            ->all(),
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Page')
        ->where('showcase.vocabulary.id', $target->id)
        ->where('showcase.cloze_question.quiz_type', 'sentence_cloze')
        ->has('showcase.cloze_question.options', 4)
        ->has('showcase.cloze_question.sentence_segments', 3)
        ->where('showcase.spelling_question.quiz_type', 'spelling')
        ->where('showcase.spelling_question.target_word', strtolower($target->word))
    );
});
