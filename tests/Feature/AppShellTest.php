<?php

test('the viewport opts into the display cutout so safe area insets resolve', function () {
    // Without `viewport-fit=cover`, iOS resolves `env(safe-area-inset-bottom)` to zero and
    // the sticky action bar's bottom padding silently collapses, putting its button back
    // underneath the home indicator.
    $response = $this->get(route('home', ['locale' => 'zh-tw']));

    $response->assertSuccessful()
        ->assertSee('viewport-fit=cover', false);
});
