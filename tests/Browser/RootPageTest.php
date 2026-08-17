<?php

use App\Models\Level;
use Database\Seeders\DefaultSeeder;
use Illuminate\Support\Facades\Lang;

beforeEach(function () {
    $this->seed(DefaultSeeder::class);
});

test('root page has no smoke', function () {
    $page = visit('/');
    $page->assertNoSmoke();
});

test('root page labels will switch by locale', function (string $locale, string $localeLabel) {
    $levels = Level::with('translations')->orderBy('id')->get();

    $page = visit('/')
        ->click('@language-switcher')
        ->click($localeLabel)
        ->assertSeeIn('@home-title', Lang::get('app.home_title', locale: $locale))
        ->assertSeeIn('@home-subtitle', Lang::get('app.home_subtitle', locale: $locale))
        ->assertSeeIn('@custom-quiz', Lang::get('app.custom_quiz', locale: $locale))
        ->assertSeeIn("@level-stats-{$levels->first()->id}", Lang::get('app.level_stats', locale: $locale));

    foreach ($levels as $level) {
        $translation = $level->translations->firstWhere('locale', $locale);

        $page->assertSeeIn("@level-name-{$level->id}", $translation->name)
            ->assertSeeIn("@level-description-{$level->id}", $translation->description);
    }
})->with('locale');

test('direct localized visit renders japanese', function () {
    visit('/ja')
        ->assertSeeIn('@home-title', Lang::get('app.home_title', locale: 'ja'))
        ->assertSeeIn('@home-subtitle', Lang::get('app.home_subtitle', locale: 'ja'));
});
