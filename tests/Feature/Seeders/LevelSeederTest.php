<?php

use App\Models\Level;
use App\Models\LevelTranslation;
use Database\Seeders\DefaultLevelSeeder;

test('default level seeder creates translations for every level', function () {
    $this->seed(DefaultLevelSeeder::class);

    expect(Level::count())->toBe(7)
        ->and(LevelTranslation::count())->toBe(21);

    Level::with('translations')->each(function (Level $level): void {
        expect($level->translations->pluck('locale')->sort()->values()->all())
            ->toBe(['ja', 'zh_CN', 'zh_TW']);
    });
});

test('default level seeder is idempotent', function () {
    $this->seed(DefaultLevelSeeder::class);

    expect(Level::count())->toBe(7)
        ->and(LevelTranslation::count())->toBe(21);

    $this->seed(DefaultLevelSeeder::class);

    expect(Level::count())->toBe(7)
        ->and(LevelTranslation::count())->toBe(21);
});
