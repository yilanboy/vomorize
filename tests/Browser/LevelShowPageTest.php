<?php

use App\Enums\Locale;
use App\Models\Level;
use Database\Seeders\DefaultSeeder;

beforeEach(function () {
    $this->seed(DefaultSeeder::class);
});

test('level show page has no smoke', function () {
    $page = visit(route('levels.show', ['level' => 1]));
    $page->assertNoSmoke();
});

test('level show page labels will switch by locale', function (string $locale, string $localeLabel) {
    $level = Level::find(1)->load('translations');
    $translation = $level->translations->firstWhere('locale', $locale);
    $routeKey = Locale::from($locale)->routeKey();

    visit(route('levels.show', ['locale' => $routeKey, 'level' => $level->id]))
        ->assertSeeIn('@level-name', $translation->name)
        ->assertSeeIn('@level-description', $translation->description);
})->with('locale');
