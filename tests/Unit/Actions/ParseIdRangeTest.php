<?php

use App\Actions\ParseIdRange;

beforeEach(function () {
    $this->action = new ParseIdRange;
});

test('parses a single ID', function () {
    expect($this->action->handle('1'))->toBe([1]);
});

test('parses comma-separated IDs', function () {
    expect($this->action->handle('1,3,5'))->toBe([1, 3, 5]);
});

test('parses a range of IDs', function () {
    expect($this->action->handle('1-5'))->toBe([1, 2, 3, 4, 5]);
});

test('parses mixed ranges and single IDs', function () {
    expect($this->action->handle('1-3,5,8-10'))->toBe([1, 2, 3, 5, 8, 9, 10]);
});

test('handles whitespace around numbers and hyphens', function () {
    expect($this->action->handle(' 1 , 3 , 5 - 7 '))->toBe([1, 3, 5, 6, 7]);
});

test('deduplicates and sorts IDs numerically', function () {
    expect($this->action->handle('10,2,5,2-4'))->toBe([2, 3, 4, 5, 10]);
});

test('returns empty array when input is null or whitespace', function () {
    expect($this->action->handle(null))->toBe([])
        ->and($this->action->handle(''))->toBe([])
        ->and($this->action->handle('   '))->toBe([]);
});

test('safely ignores inverted ranges or invalid range formats', function () {
    expect($this->action->handle('5-1'))->toBe([])
        ->and($this->action->handle('1-2-3'))->toBe([])
        ->and($this->action->handle('1-, -5'))->toBe([])
        ->and($this->action->handle('1,,3'))->toBe([1, 3]);
});
