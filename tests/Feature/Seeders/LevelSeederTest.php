<?php

declare(strict_types=1);

use App\Enums\Locale;
use App\Models\Level;
use App\Models\LevelTranslation;
use Database\Seeders\LevelSeeder;

test('level seeder creates all 7 levels with correct names and descriptions', function () {
    $this->seed(LevelSeeder::class);

    $levels = Level::query()->with('translation')->orderBy('id')->get()->keyBy('id');

    expect($levels)->toHaveCount(7)
        ->and($levels[1]->name)->toBe('基礎入門')
        ->and($levels[1]->description)->toBe('核心常用 1,000 單字')
        ->and($levels[7]->name)->toBe('額外補充')
        ->and($levels[7]->description)->toBe('進階加分詞彙');
});

test('level seeder populates complete non-empty translations for all supported locales', function () {
    $this->seed(LevelSeeder::class);

    $supportedLocales = Locale::values();
    $levels = Level::query()->with('translations')->orderBy('id')->get();

    expect(LevelTranslation::count())->toBe(7 * count($supportedLocales));

    foreach ($levels as $level) {
        expect($level->translations->pluck('locale')->sort()->values()->all())
            ->toBe(collect($supportedLocales)->sort()->values()->all());

        foreach ($level->translations as $translation) {
            expect(trim($translation->name))->not->toBe('')
                ->and(trim($translation->description))->not->toBe('');
        }
    }
});

test('level seeder is idempotent', function () {
    $this->seed(LevelSeeder::class);

    expect(Level::count())->toBe(7);

    $this->seed(LevelSeeder::class);

    expect(Level::count())->toBe(7);
});
