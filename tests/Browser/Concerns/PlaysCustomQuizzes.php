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

        for ($count = 1; $count <= $questionCount; $count++) {
            $selector = $count <= $correctTarget ? '@correct-option' : '@wrong-option';

            $page
                ->assertSeeIn('@current-step', (string) $count)
                ->assertDisabled('@continue-button')
                ->assertEnabled($selector)
                ->click($selector)
                ->assertEnabled('@continue-button')
                ->click('@continue-button');
        }
    }

    protected function playSpellingQuiz(
        AwaitableWebpage|PendingAwaitablePage $page,
        int $questionCount,
    ): void {
        for ($count = 1; $count <= $questionCount; $count++) {
            $page
                ->assertSeeIn('@current-step', (string) $count)
                ->assertDisabled('@continue-button');

            $tiles = $page->script(<<<'JS'
                    Array.from(document.querySelectorAll('[data-test^="tile-"]'))
                        .map((el) => Number(el.dataset.test.replace('tile-', '')))
                        .sort((a, b) => a - b)
                        .map((index) => `tile-${index}`);
                JS);

            foreach ($tiles as $tile) {
                $page->click("@{$tile}");
            }

            $page->assertEnabled('@continue-button')
                ->click('@continue-button');
        }
    }
}
