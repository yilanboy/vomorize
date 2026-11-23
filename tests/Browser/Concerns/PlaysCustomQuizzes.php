<?php

declare(strict_types=1);

namespace Tests\Browser\Concerns;

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

trait PlaysCustomQuizzes
{
    protected function playCustomQuiz(
        AwaitableWebpage|PendingAwaitablePage $page,
        int $questionCount,
        int $wrongCount = 0,
    ): void {
        $correctTarget = 20 - $wrongCount;

        $page->assertNotPresent('footer');

        for ($count = 1; $count <= $questionCount; $count++) {
            $selector = $count <= $correctTarget ? '@correct-option' : '@wrong-option';

            $page->assertSeeIn('@current-step', (string) $count)
                ->assertDisabled('@continue-button')
                ->assertEnabled($selector)
                ->click($selector)
                ->assertEnabled('@continue-button')
                ->click('@continue-button');
        }
    }
}
