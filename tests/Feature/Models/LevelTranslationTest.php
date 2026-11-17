<?php

declare(strict_types=1);

use App\Models\Level;
use App\Models\LevelTranslation;

test('level translation level relation returns the parent level', function () {
    $level = Level::factory()
        ->withTranslation('zh_TW', '基礎入門', '繁中描述')
        ->create();

    $translation = LevelTranslation::query()
        ->where('level_id', $level->id)
        ->where('locale', 'zh_TW')
        ->firstOrFail();

    $parentLevel = $translation->level;

    expect($parentLevel)
        ->toBeInstanceOf(Level::class)
        ->id->toBe($level->id)
        ->name->toBe('基礎入門');
});
