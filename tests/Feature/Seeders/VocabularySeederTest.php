<?php

declare(strict_types=1);

use App\Enums\Locale;
use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Database\Seeders\LevelSeeder;
use Database\Seeders\VocabularySeeder;

test('vocabulary seeder creates groups and vocabularies for all levels', function () {
    $this->seed(LevelSeeder::class);
    $this->seed(VocabularySeeder::class);

    expect(Group::count())->toBe(700)
        ->and(Vocabulary::count())->toBe(7000);

    foreach (range(1, 7) as $levelId) {
        $level = Level::find($levelId);

        expect($level->groups()->count())->toBe(100)
            ->and($level->vocabularies()->count())->toBe(1000);
    }

    expect(
        Group::query()
            ->withCount('vocabularies')
            ->get()
            ->pluck('vocabularies_count')
            ->unique()
            ->values()
            ->all()
    )->toBe([10]);

    $vocabulary = Vocabulary::query()->with('translation')->firstOrFail();
    expect($vocabulary->level_id)->toBe(1)
        ->and($vocabulary->group_id)->toBe(1)
        ->and($vocabulary->word)->not->toBeEmpty()
        ->and($vocabulary->definition)->not->toBeEmpty()
        ->and($vocabulary->example_sentence)->not->toBeEmpty()
        ->and($vocabulary->example_translation)->not->toBeEmpty();
});

test('vocabulary seeder populates complete non-empty translations for all supported locales', function () {
    $this->seed(LevelSeeder::class);
    $this->seed(VocabularySeeder::class);

    $supportedLocales = Locale::values();
    $expectedPerLocale = 7000;

    expect(VocabularyTranslation::count())->toBe($expectedPerLocale * count($supportedLocales));

    foreach ($supportedLocales as $locale) {
        $localeCount = VocabularyTranslation::query()
            ->where('locale', $locale)
            ->count();

        $emptyCount = VocabularyTranslation::query()
            ->where('locale', $locale)
            ->where(function ($query): void {
                $query->where('definition', '')
                    ->orWhere('example_translation', '');
            })
            ->count();

        expect($localeCount)->toBe($expectedPerLocale)
            ->and($emptyCount)->toBe(0);
    }
});

test('vocabulary seeder is idempotent', function () {
    $this->seed(LevelSeeder::class);
    $this->seed(VocabularySeeder::class);

    expect(Group::count())->toBe(700)
        ->and(Vocabulary::count())->toBe(7000);

    $this->seed(VocabularySeeder::class);

    expect(Group::count())->toBe(700)
        ->and(Vocabulary::count())->toBe(7000);
});
