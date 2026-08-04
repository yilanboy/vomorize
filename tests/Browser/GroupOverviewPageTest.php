<?php

use App\Models\Group;
use Database\Seeders\VocabularySeeder;
use Illuminate\Support\Facades\Lang;

beforeEach(function () {
    $this->seed(VocabularySeeder::class);
});

test('group overview page has no smoke', function () {
    $page = visit(route('groups.show', ['group' => 1]));
    $page->assertNoSmoke();
});

test('group overview labels will switch by locale', function (string $locale, string $localeLabel) {
    $group = Group::find(1)->load('vocabularies');
    $stage = 0;
    $vocabulary = $group->vocabularies->first();

    visit(route('groups.show', ['group' => $group->id]))
        ->click('@language-switcher')
        ->click($localeLabel)
        ->assertSeeIn('@group-title', Lang::get('app.group_title', ['id' => $group->id], $locale))
        ->assertSeeIn('@core-vocab-count',
            Lang::get('app.core_vocab_count', ['count' => $group->vocabularies->count()], $locale))
        ->assertSeeIn('@vocab-preview', Lang::get(key: 'app.vocab_preview', locale: $locale))
        ->assertSeeIn('@group-status', Lang::get('app.not_started', locale: $locale))
        ->assertSeeIn('@current-stage', Lang::get('app.current_stage', [
            'stage' => $stage,
            'total' => 6,
        ], $locale))
        ->assertSeeIn('@last-score', Lang::get('app.last_score', locale: $locale))
        ->assertSeeIn('@last-score', Lang::get('app.no_record', locale: $locale))
        ->assertSeeIn("@pronunciation-{$vocabulary->id}", Lang::get('app.pronunciation', locale: $locale))
        ->assertSeeIn("@sentence-{$vocabulary->id}", Lang::get('app.sentence', locale: $locale))
        ->assertSeeIn('@start-learning', Lang::get('app.start_learning', locale: $locale));
})->with('locale');
