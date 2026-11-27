<?php

declare(strict_types=1);

use App\Models\SentenceClozeQuestion;

test('returns a successful response', function () {
    SentenceClozeQuestion::factory()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
});
