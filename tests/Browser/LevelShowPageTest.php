<?php

use App\Models\Level;
use Database\Seeders\VocabularySeeder;

beforeEach(function () {
    $this->seed(VocabularySeeder::class);
});

test('level show page has no smoke', function () {
    $page = visit(route('levels.show', ['level' => 1]));
    $page->assertNoSmoke();
});

test('level show page labels will switch by locale', function (string $locale, string $localeLabel) {
    $level = Level::find(1)->load('translations');
    $translation = $level->translations->firstWhere('locale', $locale);

    visit(route('levels.show', ['level' => $level->id]))
        ->click('@language-switcher')
        ->click($localeLabel)
        ->assertSeeIn('@level-name', $translation->name)
        ->assertSeeIn('@level-description', $translation->description);
})->with('locale');
