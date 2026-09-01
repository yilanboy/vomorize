<?php

use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
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

    $vocabulary = Vocabulary::first();
    expect($vocabulary->level_id)->toBe(1)
        ->and($vocabulary->group_id)->toBe(1)
        ->and($vocabulary->word)->not->toBeEmpty()
        ->and($vocabulary->definition)->not->toBeEmpty()
        ->and($vocabulary->example_sentence)->not->toBeEmpty()
        ->and($vocabulary->example_translation)->not->toBeEmpty();
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
