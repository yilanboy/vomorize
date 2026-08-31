<?php

use App\Enums\Locale;
use App\Models\Level;
use Database\Seeders\DefaultVocabularySeeder;

beforeEach(function () {
    $this->seed(DefaultVocabularySeeder::class);
});

test('level show page has no smoke', function (Locale $locale) {
    $page = visit(route('levels.show', ['locale' => $locale->routeKey(), 'level' => 1]));
    $page->assertNoSmoke();
})->with(Locale::cases());

test('level show page labels will switch by locale', function (Locale $locale) {
    $level = Level::find(1)->load('translations');
    $translation = $level->translations->firstWhere('locale', $locale->value);

    visit(route('levels.show', ['locale' => $locale->routeKey(), 'level' => $level->id]))
        ->assertSeeIn('@level-name', $translation->name)
        ->assertSeeIn('@level-description', $translation->description);
})->with(Locale::cases());
