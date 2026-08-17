<?php

use App\Enums\Locale;

it('will return the route keys', function () {
    expect(Locale::routeKeys())->toEqualCanonicalizing(['zh-tw', 'zh-cn', 'ja']);
});
