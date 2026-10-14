<?php

use App\Actions\CalculateGroupStatusCounts;

test('calculates counts correctly for collection of groups', function () {
    $action = new CalculateGroupStatusCounts;

    $groups = collect([
        ['status' => 'ready'],
        ['status' => 'ready'],
        ['status' => 'cooldown'],
        ['status' => 'not_started'],
        ['status' => 'not_started'],
        ['status' => 'not_started'],
        ['status' => 'completed'],
    ]);

    $counts = $action->handle($groups);

    expect($counts)->toBe([
        'all' => 7,
        'ready' => 2,
        'cooldown' => 1,
        'not_started' => 3,
        'completed' => 1,
    ]);
});

test('returns zeros when collection is empty', function () {
    $action = new CalculateGroupStatusCounts;

    $counts = $action->handle(collect());

    expect($counts)->toBe([
        'all' => 0,
        'ready' => 0,
        'cooldown' => 0,
        'not_started' => 0,
        'completed' => 0,
    ]);
});
