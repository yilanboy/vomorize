<?php

use App\Enums\Locale;
use App\Models\Level;
use Database\Seeders\DefaultVocabularySeeder;
use Illuminate\Support\Facades\Lang;

test('root page has no smoke', function (Locale $locale) {
    $page = visit("/$locale->value");
    $page->assertNoSmoke();
})->with(Locale::cases());

test('root page labels will switch by locale', function (Locale $locale) {
    $this->seed(DefaultVocabularySeeder::class);

    $levels = Level::with('translations')->orderBy('id')->get();

    $page = visit('/')
        ->click('@language-switcher')
        ->click($locale->label())
        ->assertSeeIn('@home-title', Lang::get('app.home_title', locale: $locale->value))
        ->assertSeeIn('@home-subtitle', Lang::get('app.home_subtitle', locale: $locale->value))
        ->assertSeeIn('@custom-quiz', Lang::get('app.custom_quiz', locale: $locale->value))
        ->assertSeeIn("@level-stats-{$levels->first()->id}", Lang::get('app.level_stats', locale: $locale->value));

    foreach ($levels as $level) {
        $translation = $level->translations->firstWhere('locale', $locale->value);

        $page->assertSeeIn("@level-name-{$level->id}", $translation->name)
            ->assertSeeIn("@level-description-{$level->id}", $translation->description);
    }
})->with(Locale::cases());
