<?php

use App\Models\Level;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guest can access welcome page and sees inertia welcome component', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
    );
});

test('authenticated user can access welcome page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
    );
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
    $level = Level::factory()->create([
        'name' => '等級 1',
        'description' => '基礎入門：核心常用 1,000 單字',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
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
        ->component('Welcome')
        ->has('levels', 7)
    );
});
