<?php

use App\Models\Group;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use Database\Seeders\DatabaseSeeder;

test('database seeder runs all seeders properly in testing environment', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Level::count())->toBe(7)
        ->and(Group::count())->toBe(700)
        ->and(Vocabulary::count())->toBe(7000)
        ->and(User::count())->toBe(1);

    // Verify idempotency
    $this->seed(DatabaseSeeder::class);
    expect(User::count())->toBe(1);
});

test('database seeder does not create test user in production environment', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

    expect(Level::count())->toBe(7)
        ->and(Group::count())->toBe(700)
        ->and(Vocabulary::count())->toBe(7000)
        ->and(User::where('email', 'test@example.com')->exists())->toBeFalse();
});
