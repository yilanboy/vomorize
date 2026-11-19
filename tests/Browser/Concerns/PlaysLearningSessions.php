<?php

declare(strict_types=1);

namespace Tests\Browser\Concerns;

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

trait PlaysLearningSessions
{
    /**
     * Play through all 30 steps of a Stage 1 intro & quiz session.
     */
    protected function completeStage1Session(
        AwaitableWebpage|PendingAwaitablePage $page,
        int $wrongCount = 0,
    ): void {
        $introSteps = [1, 2, 5, 6, 11, 12, 17, 18, 23, 24];
        $quizIndex = 0;
        $correctTarget = 20 - $wrongCount;

        $page->assertNotPresent('footer');

        for ($step = 1; $step <= 30; $step++) {
            $page->assertSeeIn('@current-step', (string) $step);

            if (in_array($step, $introSteps, true)) {
                $page->assertEnabled('@continue-button')->click('@continue-button');
            } else {
                $quizIndex++;
                $selector = $quizIndex <= $correctTarget ? '@correct-option' : '@wrong-option';
                $page->assertDisabled('@continue-button')
                    ->assertEnabled($selector)
                    ->click($selector)
                    ->assertEnabled('@continue-button')
                    ->click('@continue-button');
            }
        }
    }

    /**
     * Play through all 20 quiz steps of a Stage 2+ review quiz session.
     */
    protected function completeReviewQuizSession(
        AwaitableWebpage|PendingAwaitablePage $page,
        int $wrongCount = 0,
    ): void {
        $correctTarget = 20 - $wrongCount;

        $page->assertNotPresent('footer');

        for ($step = 1; $step <= 20; $step++) {
            $selector = $step <= $correctTarget ? '@correct-option' : '@wrong-option';

            $page->assertSeeIn('@current-step', (string) $step)
                ->assertDisabled('@continue-button')
                ->assertEnabled($selector)
                ->click($selector)
                ->assertEnabled('@continue-button')
                ->click('@continue-button');
        }
    }
}
