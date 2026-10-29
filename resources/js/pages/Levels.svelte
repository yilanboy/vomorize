<script lang="ts">
    import { onMount } from 'svelte';
    import { Link, setLayoutProps } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import BookOpen from '@lucide/svelte/icons/book-open';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import AppHead from '@/components/AppHead.svelte';
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
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

    function parseLevelInfo(item: LevelItem) {
        if (item.description?.includes('：')) {
            const [title, ...rest] = item.description.split('：');
            return {
                title: title.trim(),
                description: rest.join('：').trim(),
            };
        }
        return {
            title: item.name,
            description: item.description,
        };
    }

    const overallMastery = $derived.by(() => {
        if (!activeProgress || levels.length === 0) {
            return 0;
        }
        const total = levels.reduce((acc, lvl) => acc + getMastery(lvl.id), 0);
        return Math.round((total / levels.length) * 10) / 10;
    });

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
            title: t('ui.levels_page.breadcrumb_home'),
            href: '/',
        },
        {
            title: t('ui.levels_page.breadcrumb'),
            href: '/levels',
        },
    ];

    setLayoutProps({
        breadcrumbs,
    });
</script>

<AppHead title={t('ui.levels_page.title')} />

<main
    class="mx-auto max-w-7xl space-y-6 px-4 py-6 selection:bg-blue-600 selection:text-white sm:space-y-8 sm:px-6 sm:py-8 lg:px-8"
>
    <!-- Breadcrumb -->
    <Breadcrumbs {breadcrumbs} />

    <!-- Level Header & Overall Progress Card -->
    <section
        class="border-border/80 bg-card rounded-2xl border p-6 shadow-xs sm:p-8"
    >
        <div
            class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="max-w-2xl">
                <h1
                    class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
                >
                    {t('ui.levels_page.title')}
                </h1>
                <p
                    class="text-muted-foreground mt-2 text-sm leading-relaxed sm:text-base"
                >
                    {t('ui.levels_page.subtitle')}
                </p>
            </div>

            <!-- Global Stats & Overall Mastery -->
            <div
                class="border-border/60 flex flex-wrap items-center gap-4 border-t pt-4 sm:gap-6 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-6"
            >
                <div>
                    <span
                        class="text-muted-foreground block text-sm font-medium"
                    >
                        全站核心單字掌握度
                    </span>
                    <span
                        class="text-foreground font-mono text-2xl font-extrabold"
                    >
                        {overallMastery}%
                    </span>
                </div>
                <div
                    class="bg-muted h-2.5 w-32 overflow-hidden rounded-full sm:w-40"
                >
                    <div
                        class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                        style={`width: ${Math.min(overallMastery, 100)}%`}
                    ></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Level Cards Grid -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        {#each levels as level (level.id)}
            {@const masteryRate = getMastery(level.id)}
            {@const parsed = parseLevelInfo(level)}
            <Link
                href={`/levels/${level.id}`}
                class="group border-border/80 bg-card relative flex flex-col justify-between rounded-2xl border p-6 shadow-2xs transition-all duration-300 outline-none hover:-translate-y-1 hover:border-blue-400 hover:shadow-md focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:hover:border-blue-600"
            >
                <div>
                    <!-- Header: Title & Mastery Status -->
                    <div class="flex items-start justify-between gap-3">
                        <h2
                            class="text-foreground text-lg font-bold transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                        >
                            {parsed.title}
                        </h2>

                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="size-4" />
                            <span>
                                {t('ui.levels_page.mastery', {
                                    rate: masteryRate,
                                })}
                            </span>
                        </span>
                    </div>

                    <!-- Level Description -->
                    <p
                        class="text-muted-foreground mt-2 line-clamp-2 text-sm leading-relaxed"
                    >
                        {parsed.description}
                    </p>
                </div>

                <!-- Footer: Stats, Progress Bar, and Action -->
                <div class="border-border/60 mt-6 border-t pt-4">
                    <div
                        class="text-muted-foreground mb-2 flex items-center justify-between text-sm font-medium"
                    >
                        <span>進度概覽</span>
                        <span>
                            {t('ui.levels_page.stats', {
                                words: (
                                    level.vocabularies_count ?? 1000
                                ).toLocaleString(),
                                groups: level.groups_count ?? 100,
                            })}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div
                        class="bg-muted mb-4 h-2 w-full overflow-hidden rounded-full"
                    >
                        <div
                            class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                            style={`width: ${Math.min(masteryRate, 100)}%`}
                        ></div>
                    </div>

                    <!-- Action Link -->
                    <div
                        class="flex items-center justify-between text-sm font-bold text-blue-600 transition-colors group-hover:text-blue-700 dark:text-blue-400 dark:group-hover:text-blue-300"
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
</main>
