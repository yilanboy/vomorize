<?php

declare(strict_types=1);

test('Mobile nav sidebar will not show on desktop', function () {
    $page = visit(route('home'))->on()->desktop();

    $page
        ->assertNoSmoke()
        ->assertMissing('@mobile-menu-button')
        ->assertMissing('@mobile-nav-sheet');
});

test('Mobile nav sidebar only shows on mobile', function () {
    $page = visit(route('home'))->on()->mobile();

    $page
        ->assertNoSmoke()
        ->assertVisible('@mobile-menu-button')
        ->click('@mobile-menu-button')
        ->assertSeeIn('@mobile-nav-sheet', __('ui.nav.home'))
        ->assertSeeIn('@mobile-nav-sheet', __('ui.nav.levels'))
        ->assertSeeIn('@mobile-nav-sheet', __('ui.nav.quiz'));
});
