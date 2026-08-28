<?php

use App\Models\Group;
use App\Models\Vocabulary;
use Database\Seeders\DefaultVocabularySeeder;
use Illuminate\Support\Facades\Lang;

beforeEach(function () {
    $this->seed(DefaultVocabularySeeder::class);
});

test('group overview page has no smoke', function () {
    $page = visit(route('groups.show', ['group' => 1]));
    $page->assertNoSmoke();
});

test('group overview labels will switch by locale', function (string $locale, string $localeLabel) {
    $group = Group::find(1)->load('vocabularies');
    $stage = 0;
    $vocabulary = $group->vocabularies->first();

    visit(route('groups.show', ['locale' => $locale, 'group' => $group->id]))
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

test('the word pronunciation audio can be played', function () {
    $page = visit(route('groups.show', ['group' => 1]));

    $vocabulary = Vocabulary::find(1);
    $wordPronunciationUrl = "https://assets.vomorize.com/vocabulary/{$vocabulary->id}/word.mp3";

    $page->script(<<<JS
        window.__audioStats = {
            playCalls: 0,
            src: '',
        };

        HTMLMediaElement.prototype.play = function () {
          const src = this.currentSrc || this.src;

          if (src === '$wordPronunciationUrl') {
              window.__audioStats.playCalls++;
              window.__audioStats.src = src;
          }

          return Promise.resolve();
      };
    JS
    );

    $page->click("[data-test=\"pronunciation-{$vocabulary->id}\"]");

    $audioStats = $page->script('() => window.__audioStats;');

    expect($audioStats['playCalls'])->toBe(1)
        ->and($audioStats['src'])->toBe($wordPronunciationUrl);
});
