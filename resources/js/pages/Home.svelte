<script lang="ts">
    import { page, Link } from '@inertiajs/svelte';
    import { currentLocale, localized, translations } from '@/lib/locale.svelte';
    import { getGuestProgressMap, deriveGroupStatus } from '@/lib/progress';
    import { onMount } from 'svelte';

    interface LevelTranslationItem {
        locale: string;
        name: string;
        description: string;
    }

    interface LevelItem {
        id: number;
        translations: Record<string, LevelTranslationItem>;
        total_groups: number;
        completed_groups: number;
        ready_groups: number;
        pending_groups: number;
    }

    let { levels = [] } = $props<{ levels: LevelItem[] }>();

    let t = $derived(translations());
    let isGuest = $derived(!page.props.auth?.user);

    let displayLevels = $derived.by<LevelItem[]>(() => {
        if (!isGuest || typeof window === 'undefined') {
            return levels;
        }

        const guestMap = getGuestProgressMap();
        const guestRecords = Object.values(guestMap);
        const now = Date.now();

        return levels.map((level: LevelItem) => {
            let completed = 0;
            let ready = 0;
            let pending = 0;

            const levelRecords = guestRecords.filter((r) => r.level_id === level.id);

            for (const p of levelRecords) {
                const status = deriveGroupStatus(p.stage, p.last_score, p.next_review_at, now);

                if (status === 'completed') {
                    completed++;
                } else if (status === 'ready') {
                    ready++;
                } else if (['locked', 'penalty'].includes(status)) {
                    pending++;
                }
            }

            return {
                ...level,
                completed_groups: completed,
                ready_groups: ready,
                pending_groups: pending,
            };
        });
    });

    function getLevelTranslation(level: LevelItem): Omit<LevelTranslationItem, 'locale'> {
        return localized<LevelTranslationItem>(level.translations) ?? { name: '', description: '' };
    }
</script>

<main class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1
                data-test="home-title"
                class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-50"
            >
                {t['home_title']}
            </h1>
            <p data-test="home-subtitle" class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {t['home_subtitle']}
            </p>
        </div>

        <Link
            href={`/${currentLocale()}/quiz/custom`}
            data-test="custom-quiz"
            class="inline-flex items-center justify-center rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-zinc-800 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-200"
        >
            {t['custom_quiz']}
        </Link>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        {#each displayLevels as level (level.id)}
            <Link
                href={`/${currentLocale()}/levels/${level.id}`}
                class="group relative flex flex-col justify-between rounded-2xl border border-zinc-200 bg-zinc-50 p-5 shadow-xs transition hover:border-zinc-400 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-600"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            data-test={`level-name-${level.id}`}
                            class="inline-flex items-center rounded-lg bg-zinc-200 px-2.5 py-1 text-sm font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50"
                        >
                            {getLevelTranslation(level).name}
                        </span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            {level.completed_groups}/{level.total_groups}
                            {t['completed']}
                        </span>
                    </div>

                    <p
                        data-test={`level-description-${level.id}`}
                        class="mt-3 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400"
                    >
                        {getLevelTranslation(level).description}
                    </p>
                </div>

                <div
                    class="mt-4 flex items-center justify-between border-t border-zinc-200 pt-3 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400"
                >
                    <span data-test={`level-stats-${level.id}`}>{t['level_stats']}</span>
                    {#if level.ready_groups > 0}
                        <span class="font-medium text-orange-500">
                            {level.ready_groups}
                            {t['ready']}
                        </span>
                    {/if}
                    {#if level.pending_groups > 0}
                        <span class="font-medium text-yellow-500">
                            {level.pending_groups}
                            {t['pending']}
                        </span>
                    {/if}
                </div>
            </Link>
        {/each}
    </div>
</main>
