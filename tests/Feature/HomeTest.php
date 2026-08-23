<?php

use App\Enums\Locale as LocaleEnum;
use App\Models\Level;
use Database\Seeders\DefaultSeeder;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(DefaultSeeder::class);
});

it('will contain all levels', function (string $routeKey) {
    $levelCount = Level::query()->count();

    get("/$routeKey")
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('levels', $levelCount)
        );
})->with(LocaleEnum::routeKeys());
