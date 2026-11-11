import { router } from '@inertiajs/svelte';
import { onMount } from 'svelte';
import { on } from 'svelte/events';

export interface NavigationGuardOptions {
    /** Returns true while navigation should be intercepted (e.g. () => !quizCompleted) */
    enabled: () => boolean;
    /** Invoked when browser back/popstate or Inertia navigation is blocked by the guard */
    onBlocked: (targetUrl?: string) => void;
}

export interface NavigationGuard {
    /** Returns true if the guard is currently active and not bypassed */
    isActive: () => boolean;
    /** Explicitly bypass the guard for confirmed leave or programmatic redirects */
    bypass: () => void;
    /** Reset the bypass state (e.g. when retrying a quiz) */
    reset: () => void;
}

export function createNavigationGuard(
    options: NavigationGuardOptions,
): NavigationGuard {
    let isBypassed = false;

    function isActive(): boolean {
        return options.enabled() && !isBypassed;
    }

    function bypass(): void {
        isBypassed = true;
    }

    function reset(): void {
        isBypassed = false;
    }

    onMount(() => {
        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            if (isActive()) {
                e.preventDefault();
            }
        };

        const handlePopState = (e: PopStateEvent) => {
            if (isActive()) {
                e.stopImmediatePropagation();
                window.history.pushState(
                    window.history.state,
                    '',
                    window.location.href,
                );
                options.onBlocked();
            }
        };

        window.history.pushState(
            window.history.state,
            '',
            window.location.href,
        );

        const removeBeforeUnload = on(
            window,
            'beforeunload',
            handleBeforeUnload,
        );
        const removePopState = on(window, 'popstate', handlePopState, {
            capture: true,
        });
        const removeInertiaBefore = router.on('before', (event) => {
            if (isActive()) {
                event.preventDefault();
                options.onBlocked(event.detail.visit.url.href);
            }
        });

        return () => {
            removeBeforeUnload();
            removePopState();
            removeInertiaBefore();
        };
    });

    return {
        isActive,
        bypass,
        reset,
    };
}
