<?php

declare(strict_types=1);

arch()->preset()->security();

arch()->preset()->laravel();

arch('app')
    ->expect('App')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);
