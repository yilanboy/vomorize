<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import AppHead from '@/components/AppHead.svelte';
    import { t } from '@/lib/i18n';
    import type { BreadcrumbItem } from '@/types';
    import { setLayoutProps } from '@inertiajs/svelte';

    interface LevelItem {
        id: number;
        name: string;
        description: string;
        vocabularies_count: number;
        groups_count: number;
        total_stages?: number;
        max_stages?: number;
        mastery_percentage: number;
    }

    let {
        levels = [],
    }: {
        levels?: LevelItem[];
    } = $props();

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
                    <!-- Header: Badge & Mastery Status -->
                    <div class="flex items-center justify-between">
                        <span
                            class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700 dark:bg-blue-950/60 dark:text-blue-300"
                        >
                            {t('ui.levels_page.level_badge', {
                                id: level.id,
                            })}
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="size-4" />
                            <span>
                                {t('ui.levels_page.mastery', {
                                    rate: level.mastery_percentage,
                                })}
                            </span>
                        </span>
                    </div>

                    <!-- Level Name -->
                    <h2
                        class="text-foreground mt-4 text-lg font-bold transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                    >
                        {level.name}
                    </h2>

                    <!-- Level Description -->
                    <p
                        class="text-muted-foreground mt-2 line-clamp-2 text-sm leading-relaxed"
                    >
                        {level.description}
                    </p>
                </div>

                <!-- Footer: Stats, Progress, and Action -->
                <div class="border-border/60 mt-6 border-t pt-4">
                    <div
                        class="text-muted-foreground mb-2 flex items-center justify-between text-sm"
                    >
                        <span>
                            {t('ui.levels_page.stats', {
                                words: (
                                    level.vocabularies_count ?? 1000
                                ).toLocaleString(),
                                groups: level.groups_count ?? 100,
                            })}
                        </span>
                    </div>

                    <!-- Progress Bar (Emerald) -->
                    <div
                        class="bg-muted mb-4 h-2 w-full overflow-hidden rounded-full"
                    >
                        <div
                            class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                            style={`width: ${level.mastery_percentage}%`}
                        ></div>
                    </div>

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
