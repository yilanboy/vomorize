<script module lang="ts">
    /**
     * Sanctions every navigation for the rest of the mounted phase's life.
     *
     * Completing a phase fires more than one navigation — the score submission, then the visit
     * to the result page — so a one-shot exemption would not cover it. Disarming outright is
     * also simply correct: once a session is complete there is nothing left to protect.
     *
     * Module scope outlives the component, so a guard resets this when it mounts. Without that,
     * the first completed session would leave every later one unguarded.
     */
    let disarmed = false;

    export function disarmLeaveGuard(): void {
        disarmed = true;
    }
</script>

<script lang="ts">
    import type { PendingVisit } from '@inertiajs/core';
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import Button from '@/components/ui/button/Button.svelte';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { translations } from '@/lib/locale.svelte';

    /**
     * Where a confirmed departure lands when the learner had no destination of their own — a back
     * press. Supplied by the phase rather than derived here, because not every guarded phase
     * belongs to a level: the practice quiz returns to its own selection screen.
     */
    let { exitUrl }: { exitUrl: string } = $props();

    let t = $derived(translations());
    let isOpen = $state(false);
    let pendingVisit = $state<PendingVisit | null>(null);

    function stay(): void {
        isOpen = false;
        pendingVisit = null;
    }

    function leave(): void {
        const visit = pendingVisit;

        isOpen = false;
        pendingVisit = null;
        disarmed = true;

        // A back press carries no destination to honour, so there is nothing to replay.
        if (!visit) {
            router.visit(exitUrl);

            return;
        }

        router.visit(visit.url, { method: visit.method, data: visit.data });
    }

    /**
     * A back press cannot be intercepted through any supported API. The client router's own
     * history listener is registered when the app boots, so it always runs before this one, and
     * it fires no cancellable event before swapping the page. It does, however, return early —
     * before any swap — for a history entry that carries no router state of its own.
     *
     * Keeping a stateless entry immediately below the current one therefore turns a back press
     * into a no-op for the router, leaving this phase mounted so the confirmation can be raised
     * instead. Two entries are pushed rather than one because the pushed entry becomes the
     * current one, and it is the entry *below* that a back press lands on.
     *
     * This leans on router behaviour that is not public API, and its failure mode is silent —
     * the guard would simply stop answering back presses. The browser test covering it is the
     * only thing that will catch an upgrade changing it.
     */
    function keepStatelessEntryBelow(): void {
        window.history.pushState(null, '', window.location.href);
        window.history.pushState(null, '', window.location.href);
    }

    onMount(() => {
        disarmed = false;

        function onPopState(): void {
            if (disarmed) {
                return;
            }

            // The router has just written its own state into the entry we landed on, so restore
            // the arrangement: make this entry stateless again and push one more. Doing it on
            // every press keeps the history a constant two entries deeper rather than growing
            // by one each time, and keeps a later press answerable.
            window.history.replaceState(null, '', window.location.href);
            window.history.pushState(null, '', window.location.href);

            isOpen = true;
        }

        /**
         * Reload, tab close, and navigation to an external address are the browser's to warn
         * about, not ours: the prompt's wording belongs to it and cannot be replaced with this
         * app's copy, and it is only shown once the learner has interacted with the page, so an
         * immediate reload on a freshly opened phase passes through silently. Both limits are
         * accepted — a partial guard on the reload path is better than none.
         *
         * Deliberately not covered by a test. Browsers suppress the prompt without prior
         * interaction and the automation harness dismisses it automatically, so a test would
         * either not exercise it or would assert the harness's behaviour rather than this app's.
         */
        function warnBeforeUnload(event: BeforeUnloadEvent): void {
            if (disarmed) {
                return;
            }

            event.preventDefault();
        }

        keepStatelessEntryBelow();
        window.addEventListener('popstate', onPopState);
        window.addEventListener('beforeunload', warnBeforeUnload);

        const stopInterceptingVisits = router.on('before', (event) => {
            if (disarmed) {
                return;
            }

            // A prefetch is not an exit. Links that prefetch on hover fire this same event, so
            // without this a learner merely hovering the user menu would be asked whether they
            // want to leave — and cancelling would break prefetching into the bargain.
            if (event.detail.visit.prefetch) {
                return;
            }

            // Still cancel while the confirmation is up, but keep the destination already being
            // asked about rather than overwriting it with a later one.
            if (isOpen) {
                return false;
            }

            pendingVisit = event.detail.visit;
            isOpen = true;

            return false;
        });

        return () => {
            window.removeEventListener('popstate', onPopState);
            window.removeEventListener('beforeunload', warnBeforeUnload);
            stopInterceptingVisits();
        };
    });
</script>

<Dialog bind:open={isOpen}>
    <DialogContent
        class="space-y-4 sm:max-w-md"
        labelledBy="leave-quiz-title"
        describedBy="leave-quiz-description"
    >
        <div class="space-y-3">
            <div id="leave-quiz-title">
                <DialogTitle>{t['leave_quiz_title']}</DialogTitle>
            </div>
            <div id="leave-quiz-description">
                <DialogDescription>
                    {t['leave_quiz_desc']}
                </DialogDescription>
            </div>
        </div>

        <DialogFooter class="gap-2">
            <Button variant="outline" class="text-red-600 dark:text-red-400" onclick={leave}>
                {t['leave_quiz_confirm']}
            </Button>

            <Button onclick={stay}>
                {t['continue_quiz']}
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
