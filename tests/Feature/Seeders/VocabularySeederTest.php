<?php

use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Database\Seeders\DefaultSeeder;

test('default level vocabulary seeder creates translations for every vocabulary', function () {
    $this->seed(DefaultSeeder::class);

    Vocabulary::with('translations')->each(function (Vocabulary $vocabulary): void {
        expect($vocabulary->translations->pluck('locale')->sort()->values()->all())
            ->toBe(['ja', 'zh-cn', 'zh-tw']);
    });
});

test('default level vocabulary seeder is idempotent', function () {
    $this->seed(DefaultSeeder::class);

    expect(Vocabulary::count())->toBe(7000)
        ->and(VocabularyTranslation::count())->toBe(21_000);

    $this->seed(DefaultSeeder::class);

    expect(Vocabulary::count())->toBe(7000)
        ->and(VocabularyTranslation::count())->toBe(21_000);
});

test('default vocabulary seeders create 1000 translated words for levels 1 through 7', function () {
    $this->seed(DefaultSeeder::class);

    foreach (range(1, 7) as $levelId) {
        expect(Vocabulary::whereHas('group', fn ($query) => $query->where('level_id', $levelId))->count())
            ->toBe(1000)
            ->and(VocabularyTranslation::whereHas('vocabulary.group', fn ($query) => $query->where('level_id', $levelId))
                ->count())
            ->toBe(3000);
    }
});
