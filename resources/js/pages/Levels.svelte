<script lang="ts">
    import { onMount } from 'svelte';
    import { Link, setLayoutProps } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import AppHead from '@/components/AppHead.svelte';
    import { t } from '@/lib/i18n';
    import {
        computeLevelsProgressMap,
        learningProgressDb,
        type LevelProgressSummaryItem,
    } from '@/lib/learningProgressDb';
    import type { BreadcrumbItem } from '@/types';

    interface LevelItem {
        id: number;
        name: string;
        description: string;
        vocabularies_count: number;
        groups_count: number;
    }

    let {
        levels = [],
        progress = null,
    }: {
        levels?: LevelItem[];
        progress?: Record<number, LevelProgressSummaryItem> | null;
    } = $props();

    let localProgress = $state<Record<number, LevelProgressSummaryItem> | null>(
        null,
    );
    const activeProgress = $derived(progress ?? localProgress);

    function getMastery(levelId: number): number {
        return activeProgress?.[levelId]?.mastery_percentage ?? 0;
    }

    onMount(async () => {
        if (progress !== null) {
            return;
        }

        const records = await learningProgressDb.getAllProgress();
        if (records.length > 0) {
            localProgress = computeLevelsProgressMap(records, levels);
        }
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('ui.levels_page.breadcrumb'),
            href: '/levels',
        },
    ];

    setLayoutProps({
        breadcrumbs: breadcrumbs,
    });
</script>

<AppHead title={t('ui.levels_page.title')} />

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
    <!-- Hero Section -->
    <header class="border-border/60 mb-8 border-b pb-6">
        <h1 class="text-foreground text-3xl font-bold tracking-tight">
            {t('ui.levels_page.title')}
        </h1>
        <p
            class="text-muted-foreground mt-2 max-w-3xl text-base leading-relaxed"
        >
            {t('ui.levels_page.subtitle')}
        </p>
    </header>

    <!-- Level Cards Grid -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        {#each levels as level (level.id)}
            <Link
                href={`/levels/${level.id}`}
                class="group bg-card text-card-foreground border-border relative flex flex-col justify-between rounded-2xl border p-6 shadow-2xs transition-all duration-300 hover:-translate-y-1 hover:border-blue-500/50 hover:shadow-md dark:hover:border-blue-500/40"
            >
                <div>
                    <!-- Header: Title & Stats Badge -->
                    <div class="flex items-start justify-between gap-3">
                        <h2
                            class="text-foreground text-lg font-bold transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                        >
                            {level.name}
                        </h2>

                        <span
                            class="bg-muted/60 text-muted-foreground shrink-0 rounded-md px-2.5 py-0.5 text-sm font-medium"
                        >
                            {t('ui.levels_page.stats', {
                                words: (
                                    level.vocabularies_count ?? 1000
                                ).toLocaleString(),
                                groups: level.groups_count ?? 100,
                            })}
                        </span>
                    </div>

                    <!-- Level Description -->
                    <p
                        class="text-muted-foreground mt-3 line-clamp-2 text-sm leading-relaxed"
                    >
                        {level.description}
                    </p>
                </div>

                <!-- Footer: Mastery and Action -->
                <div
                    class="border-border/60 mt-6 flex items-center justify-between border-t pt-4"
                >
                    <!-- Mastery Status -->
                    <span
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        <CheckCircle2 class="size-4" />
                        <span>
                            {t('ui.levels_page.mastery', {
                                rate: getMastery(level.id),
                            })}
                        </span>
                    </span>

                    <!-- Action Link -->
                    <div
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors group-hover:text-blue-700 dark:text-blue-400 dark:group-hover:text-blue-300"
                    >
                        <span>{t('ui.levels_page.enter_level')}</span>
                        <ArrowRight
                            class="size-4 transition-transform duration-300 group-hover:translate-x-1"
                        />
                    </div>
                </div>
            </Link>
        {/each}
    </div>
</div>
