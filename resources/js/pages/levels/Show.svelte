<script lang="ts">
    import { Link, setLayoutProps } from '@inertiajs/svelte';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import Circle from '@lucide/svelte/icons/circle';
    import Clock from '@lucide/svelte/icons/clock';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Zap from '@lucide/svelte/icons/zap';
    import AppHead from '@/components/AppHead.svelte';
    import { t } from '@/lib/i18n';
    import type { BreadcrumbItem } from '@/types';

    interface LevelData {
        id: number;
        name: string;
        description: string;
        vocabularies_count: number;
        groups_count: number;
        total_stages: number;
        max_stages: number;
        mastery_percentage: number;
    }

    interface GroupData {
        id: number;
        sequence: number;
        status: 'ready' | 'cooldown' | 'not_started' | 'completed';
        stage: number;
        next_review_at: string | null;
        cooldown_remaining_seconds: number | null;
        vocabularies_count: number;
    }

    interface StatusCounts {
        all: number;
        ready: number;
        cooldown: number;
        not_started: number;
        completed: number;
    }

    let {
        level,
        groups = [],
        counts = {
            all: 0,
            ready: 0,
            cooldown: 0,
            not_started: 0,
            completed: 0,
        },
    }: {
        level: LevelData;
        groups?: GroupData[];
        counts?: StatusCounts;
    } = $props();

    type FilterKey = 'all' | 'ready' | 'cooldown' | 'not_started' | 'completed';
    let activeFilter = $state<FilterKey>('all');

    let filteredGroups = $derived(
        activeFilter === 'all'
            ? groups
            : groups.filter((g) => g.status === activeFilter),
    );

    const breadcrumbs = $derived<BreadcrumbItem[]>([
        {
            title: t('ui.level_detail.breadcrumb_levels'),
            href: '/levels',
        },
        {
            title: t('ui.level_detail.breadcrumb_level', { id: level.id }),
            href: `/levels/${level.id}`,
        },
    ]);

    $effect(() => {
        setLayoutProps({
            breadcrumbs: breadcrumbs,
        });
    });

    function getStatusLabel(status: GroupData['status']): string {
        switch (status) {
            case 'completed':
                return t('ui.level_detail.completed_label');
            case 'ready':
                return t('ui.level_detail.ready_label');
            case 'cooldown':
                return t('ui.level_detail.status.cooldown');
            case 'not_started':
            default:
                return t('ui.level_detail.not_started_label');
        }
    }
</script>

<AppHead
    title={t('ui.level_detail.title', { id: level.id, name: level.name })}
/>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
    <!-- Simplified Clean Page Header -->
    <header class="border-border/60 mb-8 border-b pb-6">
        <h1 class="text-foreground text-3xl font-bold tracking-tight">
            {t('ui.level_detail.title', { id: level.id, name: level.name })}
        </h1>
        <p
            class="text-muted-foreground mt-2 max-w-3xl text-base leading-relaxed"
        >
            {t('ui.level_detail.subtitle', {
                words: (
                    level.vocabularies_count ?? level.groups_count * 10
                ).toLocaleString(),
                groups: level.groups_count,
            })}
        </p>
    </header>

    <!-- Filter Pills Bar (No Extra Start Next Batch Button) -->
    <div
        class="mb-6 flex flex-wrap items-center gap-2 sm:gap-3"
        role="group"
        aria-label="Filter units by status"
    >
        <!-- All -->
        <button
            type="button"
            onclick={() => (activeFilter = 'all')}
            class={{
                'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200': true,
                'bg-slate-900 text-white shadow-xs dark:bg-slate-100 dark:text-slate-900':
                    activeFilter === 'all',
                'bg-card text-foreground border-border hover:bg-muted/80 border':
                    activeFilter !== 'all',
            }}
        >
            <span
                >{t('ui.level_detail.filters.all', { count: counts.all })}</span
            >
        </button>

        <!-- Ready -->
        {#if counts.ready > 0}
            <button
                type="button"
                onclick={() => (activeFilter = 'ready')}
                class={{
                    'inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition-all duration-200': true,
                    'border-blue-600 bg-blue-600 text-white shadow-xs dark:border-blue-600 dark:bg-blue-600 dark:text-white':
                        activeFilter === 'ready',
                    'border-blue-200 bg-blue-50/80 text-blue-700 hover:bg-blue-100/80 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300':
                        activeFilter !== 'ready',
                }}
            >
                <Zap class="size-4" />
                <span
                    >{t('ui.level_detail.filters.ready', {
                        count: counts.ready,
                    })}</span
                >
            </button>
        {/if}

        <!-- Cooldown -->
        {#if counts.cooldown > 0}
            <button
                type="button"
                onclick={() => (activeFilter = 'cooldown')}
                class={{
                    'inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition-all duration-200': true,
                    'border-amber-600 bg-amber-600 text-white shadow-xs dark:border-amber-600 dark:bg-amber-600 dark:text-white':
                        activeFilter === 'cooldown',
                    'border-amber-200 bg-amber-50/80 text-amber-800 hover:bg-amber-100/80 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300':
                        activeFilter !== 'cooldown',
                }}
            >
                <Clock class="size-4" />
                <span
                    >{t('ui.level_detail.filters.cooldown', {
                        count: counts.cooldown,
                    })}</span
                >
            </button>
        {/if}

        <!-- Not Started -->
        {#if counts.not_started > 0}
            <button
                type="button"
                onclick={() => (activeFilter = 'not_started')}
                class={{
                    'inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition-all duration-200': true,
                    'border-slate-700 bg-slate-700 text-white shadow-xs dark:border-slate-200 dark:bg-slate-200 dark:text-slate-900':
                        activeFilter === 'not_started',
                    'bg-card text-muted-foreground border-border hover:bg-muted/80 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-400':
                        activeFilter !== 'not_started',
                }}
            >
                <Circle class="size-4 stroke-[1.5]" />
                <span
                    >{t('ui.level_detail.filters.not_started', {
                        count: counts.not_started,
                    })}</span
                >
            </button>
        {/if}

        <!-- Completed -->
        {#if counts.completed > 0}
            <button
                type="button"
                onclick={() => (activeFilter = 'completed')}
                class={{
                    'inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition-all duration-200': true,
                    'border-emerald-600 bg-emerald-600 text-white shadow-xs dark:border-emerald-600 dark:bg-emerald-600 dark:text-white':
                        activeFilter === 'completed',
                    'border-emerald-200 bg-emerald-50/80 text-emerald-800 hover:bg-emerald-100/80 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300':
                        activeFilter !== 'completed',
                }}
            >
                <CheckCircle2 class="size-4" />
                <span
                    >{t('ui.level_detail.filters.completed', {
                        count: counts.completed,
                    })}</span
                >
            </button>
        {/if}
    </div>

    <!-- 100-Group Matrix (10x10 Grid) -->
    {#if filteredGroups.length === 0}
        <div
            class="bg-card border-border flex flex-col items-center justify-center rounded-2xl border p-12 text-center"
        >
            <p class="text-foreground text-base font-semibold">
                {t('ui.level_detail.empty_filter', {
                    filter: t(`ui.level_detail.status.${activeFilter}`),
                })}
            </p>
            <button
                type="button"
                onclick={() => (activeFilter = 'all')}
                class="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
            >
                <RotateCcw class="size-4" />
                <span>{t('ui.level_detail.show_all')}</span>
            </button>
        </div>
    {:else}
        <div
            class="grid grid-cols-4 gap-2.5 sm:grid-cols-5 sm:gap-3 md:grid-cols-8 lg:grid-cols-10"
        >
            {#each filteredGroups as group (group.id)}
                <Link
                    href={`/levels/${level.id}/groups/${group.sequence}`}
                    title={t('ui.level_detail.card_aria', {
                        number: group.sequence,
                        status: getStatusLabel(group.status),
                    })}
                    aria-label={t('ui.level_detail.card_aria', {
                        number: group.sequence,
                        status: getStatusLabel(group.status),
                    })}
                    class={{
                        'group relative flex aspect-square flex-col items-center justify-center rounded-xl p-2 transition-all duration-200 sm:p-3': true,
                        // Completed
                        'border border-emerald-300 bg-emerald-50/90 text-emerald-800 shadow-2xs hover:border-emerald-400 hover:shadow-sm dark:border-emerald-800/80 dark:bg-emerald-950/30 dark:text-emerald-300 dark:hover:border-emerald-700':
                            group.status === 'completed',
                        // Ready
                        'border-2 border-blue-500 bg-blue-50/90 text-blue-700 shadow-xs ring-2 ring-blue-500/20 hover:border-blue-600 hover:shadow-md dark:border-blue-500 dark:bg-blue-950/50 dark:text-blue-300 dark:ring-blue-400/20 dark:hover:border-blue-400':
                            group.status === 'ready',
                        // Cooldown
                        'border border-amber-300 bg-amber-50/80 text-amber-800 hover:border-amber-400 hover:shadow-sm dark:border-amber-800/80 dark:bg-amber-950/30 dark:text-amber-300 dark:hover:border-amber-700':
                            group.status === 'cooldown',
                        // Not Started
                        'bg-card text-muted-foreground border-border hover:text-foreground border hover:border-slate-400 hover:shadow-2xs dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-slate-200':
                            group.status === 'not_started',
                    }}
                >
                    <!-- Status micro icon in top-right corner -->
                    <div class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2">
                        {#if group.status === 'completed'}
                            <CheckCircle2
                                class="size-3.5 text-emerald-600 sm:size-4 dark:text-emerald-400"
                            />
                        {:else if group.status === 'ready'}
                            <Zap
                                class="size-3.5 fill-blue-500/20 text-blue-600 sm:size-4 dark:text-blue-400"
                            />
                        {:else if group.status === 'cooldown'}
                            <Clock
                                class="size-3.5 text-amber-600 sm:size-4 dark:text-amber-400"
                            />
                        {:else}
                            <Circle
                                class="size-3.5 stroke-[1.5] text-slate-300 sm:size-4 dark:text-slate-600"
                            />
                        {/if}
                    </div>

                    <!-- Center: Sequence Number Only -->
                    <span
                        class="font-bubble text-lg font-semibold tracking-wide sm:text-2xl"
                    >
                        {group.sequence}
                    </span>
                </Link>
            {/each}
        </div>
    {/if}
</div>
