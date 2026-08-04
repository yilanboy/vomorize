<?php

use App\Models\User;

test('the initial appearance is exposed to the client before the app mounts', function () {
    $response = $this->withUnencryptedCookie('appearance', 'dark')->get('/');

    $response->assertSuccessful()
        ->assertSee('data-appearance="dark"', false)
        ->assertSee('root.dataset.appearance = appearance;', false);
});

test('the appearance settings page is gone, so choosing a theme never requires an account', function () {
    $response = $this->actingAs(User::factory()->create())->get('/settings/appearance');

    $response->assertNotFound();
});
