<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { currentLocale, translations } from '@/lib/locale.svelte';
    import {
        show as showRoute,
        introduce as introduceRoute,
        quiz as quizRoute,
    } from '@/routes/groups';

    let { group, result } = $props<{
        group: { id: number; level_id: number; sequence: number };
        result: { phase: 'introduce' | 'quiz'; score: number; passed: boolean };
    }>();

    let t = $derived(translations());
    let retryHref = $derived(
        result.phase === 'introduce'
            ? introduceRoute.url({ locale: currentLocale(), group: group.id })
            : quizRoute.url({ locale: currentLocale(), group: group.id }),
    );
</script>

<main class="mx-auto w-full max-w-2xl px-4 py-8 sm:px-6">
    <div class="space-y-6 rounded-2xl border border-zinc-200 bg-zinc-50 p-8 text-center shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div
            class="mx-auto flex h-16 w-16 items-center justify-center rounded-full {result.passed
                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                : 'bg-rose-500/10 text-rose-700 dark:text-rose-300'}"
        >
            {result.passed ? '✓' : '×'}
        </div>
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">{t['summary']}</h1>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {t['score']}：<strong class="text-2xl text-zinc-900 dark:text-zinc-50"
                    >{result.score}%</strong
                >
            </p>
        </div>
        <div
            class="rounded-xl p-4 text-sm font-medium {result.passed
                ? 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                : 'border border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300'}"
        >
            {#if result.passed}
                {t['passed']}
            {:else if result.phase === 'introduce'}
                {t['intro_failed']}
            {:else}
                {t['failed']}
            {/if}
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            {#if !result.passed}
                <Link
                    href={retryHref}
                    class="inline-flex flex-1 justify-center rounded-xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-zinc-50 dark:text-zinc-950"
                >
                    {result.phase === 'introduce'
                        ? t['retry']
                        : t['retry_in']}
                </Link>
            {/if}
            <Link
                href={showRoute.url({ locale: currentLocale(), group: group.id })}
                class="inline-flex flex-1 justify-center rounded-xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-900 dark:border-zinc-800 dark:text-zinc-50"
            >
                {t['back_to_group']}
            </Link>
        </div>
    </div>
</main>
