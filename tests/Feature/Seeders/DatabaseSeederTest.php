<?php

use App\Models\Group;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use Database\Seeders\DatabaseSeeder;

test('database seeder runs all seeders properly', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Level::count())->toBe(7)
        ->and(Group::count())->toBe(700)
        ->and(Vocabulary::count())->toBe(7000)
        ->and(User::count())->toBe(1);
});
