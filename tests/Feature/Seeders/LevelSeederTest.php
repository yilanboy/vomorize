<?php

use App\Models\Level;
use Database\Seeders\LevelSeeder;

test('level seeder creates all 7 levels with correct names and descriptions', function () {
    $this->seed(LevelSeeder::class);

    expect(Level::count())->toBe(7)
        ->and(Level::find(1)->name)->toBe('等級 1')
        ->and(Level::find(1)->description)->toBe('基礎入門：核心常用 1,000 單字')
        ->and(Level::find(7)->name)->toBe('等級 7')
        ->and(Level::find(7)->description)->toBe('額外補充：進階加分詞彙');
});

test('level seeder is idempotent', function () {
    $this->seed(LevelSeeder::class);

    expect(Level::count())->toBe(7);

    $this->seed(LevelSeeder::class);

    expect(Level::count())->toBe(7);
});
