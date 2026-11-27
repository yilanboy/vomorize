<script lang="ts">
    import { onMount } from 'svelte';
    import { page, setLayoutProps } from '@inertiajs/svelte';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import Circle from '@lucide/svelte/icons/circle';
    import Clock from '@lucide/svelte/icons/clock';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Zap from '@lucide/svelte/icons/zap';
    import { show as showGroup } from '@/actions/App/Http/Controllers/GroupController';
    import { show as introGroup } from '@/actions/App/Http/Controllers/GroupIntroController';
    import AppHead from '@/components/AppHead.svelte';
    import TactileTile, {
        type TactileTileVariant,
    } from '@/components/TactileTile.svelte';
    import { t } from '@/lib/i18n';
    import {
        computeLevelProgressData,
        learningProgressDb,
        type GroupProgressSummary,
        type LevelGroupsProgressData,
    } from '@/lib/learningProgressDb';
    import { index as levelsIndex, show as showLevel } from '@/routes/levels';
    import type { BreadcrumbItem } from '@/types';

    interface LevelData {
        id: number;
        name: string;
        description: string;
        vocabularies_count: number;
        groups_count: number;
    }

    interface GroupData {
        id: number;
        sequence: number;
        vocabularies_count: number;
    }

    let {
        level,
        groups = [],
        progress = null,
    }: {
        level: LevelData;
        groups?: GroupData[];
        progress?: LevelGroupsProgressData | null;
    } = $props();

    type FilterKey = 'all' | 'ready' | 'cooldown' | 'not_started' | 'completed';
    let activeFilter = $state<FilterKey>('all');
    let localProgress = $state<LevelGroupsProgressData | null>(null);
    const activeProgress = $derived(progress ?? localProgress);

    function getGroupStatus(
        groupId: number,
    ): 'ready' | 'cooldown' | 'not_started' | 'completed' {
        return activeProgress?.groups?.[groupId]?.status ?? 'not_started';
    }

    const counts = $derived(
        activeProgress?.counts ?? {
            all: groups.length,
            ready: 0,
            cooldown: 0,
            not_started: groups.length,
            completed: 0,
        },
    );

    const completionRate = $derived(
        counts.all > 0 ? Math.round((counts.completed / counts.all) * 100) : 0,
    );

    let filteredGroups = $derived(
        activeFilter === 'all'
            ? groups
            : groups.filter((g) => getGroupStatus(g.id) === activeFilter),
    );

    onMount(async () => {
        if (progress !== null) {
            return;
        }

        const records = await learningProgressDb.getLevelProgressList(level.id);
        if (records.length > 0) {
            localProgress = computeLevelProgressData(records, groups.length);
        }
    });

    const breadcrumbs: BreadcrumbItem[] = $derived([
        {
            title: t('ui.level_detail.breadcrumb_levels'),
            href: levelsIndex.url(),
        },
        {
            title: t('ui.level_detail.title', { name: level.name }),
            href: showLevel.url(level.id),
        },
    ]);

    $effect.pre(() => {
        setLayoutProps({
            breadcrumbs,
        });
    });

    const statusVariants: Record<
        GroupProgressSummary['status'],
        TactileTileVariant
    > = {
        completed: 'emerald',
        ready: 'primary',
        cooldown: 'amber',
        not_started: 'muted',
    };

    function getStatusLabel(status: GroupProgressSummary['status']): string {
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

<AppHead title={t('ui.level_detail.title', { name: level.name })} />

<main
    class="mx-auto max-w-7xl space-y-6 px-4 py-6 selection:bg-blue-600 selection:text-white sm:space-y-8 sm:px-6 sm:py-8 lg:px-8"
>
    <!-- Level Overview Header -->
    <header
        class="border-border/80 flex flex-col justify-between gap-5 border-b pb-4 md:flex-row md:items-end md:pb-6"
    >
        <div class="max-w-2xl">
            <h1
                class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
            >
                {t('ui.level_detail.title', { name: level.name })}
            </h1>
            <p
                class="text-muted-foreground mt-2 hidden text-sm leading-relaxed sm:text-base md:block"
            >
                {t('ui.level_detail.subtitle', {
                    words: (
                        level.vocabularies_count ??
                        level.groups_count * page.props.vocabularyPerGroup
                    ).toLocaleString(),
                    groups: level.groups_count,
                })}
            </p>
        </div>

        <!-- Summary Progress Stats -->
        <div class="hidden shrink-0 text-right md:block">
            <span class="text-muted-foreground block text-sm font-medium">
                {t('ui.level_detail.filters.completed', {
                    count: counts.completed,
                })}
            </span>
            <span
                class="text-foreground font-mono text-2xl font-extrabold tabular-nums"
            >
                {completionRate}%
            </span>
        </div>
    </header>

    <!-- Filter Pills Bar -->
    <div
        class="flex flex-wrap items-center gap-2 sm:gap-2.5"
        role="tablist"
        aria-label="Filter units by status"
    >
        <!-- All -->
        <button
            type="button"
            role="tab"
            aria-selected={activeFilter === 'all'}
            onclick={() => (activeFilter = 'all')}
            class={{
                'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition-all duration-200 focus-visible:ring-2 focus-visible:ring-blue-500': true,
                'bg-slate-900 text-white shadow-xs dark:bg-slate-100 dark:text-slate-900':
                    activeFilter === 'all',
                'bg-card text-foreground border-border/80 hover:bg-muted/80 border':
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
                role="tab"
                aria-selected={activeFilter === 'ready'}
                onclick={() => (activeFilter = 'ready')}
                class={{
                    'inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-all duration-200 focus-visible:ring-2 focus-visible:ring-blue-500': true,
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
                role="tab"
                aria-selected={activeFilter === 'cooldown'}
                onclick={() => (activeFilter = 'cooldown')}
                class={{
                    'inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-all duration-200 focus-visible:ring-2 focus-visible:ring-amber-500': true,
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
                role="tab"
                aria-selected={activeFilter === 'not_started'}
                onclick={() => (activeFilter = 'not_started')}
                class={{
                    'inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-all duration-200 focus-visible:ring-2 focus-visible:ring-slate-500': true,
                    'border-slate-800 bg-slate-800 text-white shadow-xs dark:border-slate-200 dark:bg-slate-200 dark:text-slate-900':
                        activeFilter === 'not_started',
                    'bg-card text-muted-foreground border-border/80 hover:bg-muted/80 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-400':
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
                role="tab"
                aria-selected={activeFilter === 'completed'}
                onclick={() => (activeFilter = 'completed')}
                class={{
                    'inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-all duration-200 focus-visible:ring-2 focus-visible:ring-emerald-500': true,
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

    <!-- The Core 100-Group Matrix -->
    <section>
        {#if filteredGroups.length === 0}
            <div
                class="border-border/80 flex flex-col items-center justify-center rounded-2xl border border-dashed py-16 text-center"
            >
                <p class="text-foreground text-base font-semibold">
                    {t('ui.level_detail.empty_filter', {
                        filter: t(`ui.level_detail.status.${activeFilter}`),
                    })}
                </p>
                <button
                    type="button"
                    onclick={() => (activeFilter = 'all')}
                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-slate-800 focus-visible:ring-2 focus-visible:ring-blue-500 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                >
                    <RotateCcw class="size-4" />
                    <span>{t('ui.level_detail.show_all')}</span>
                </button>
            </div>
        {:else}
            <div
                class="grid grid-cols-4 gap-3 sm:grid-cols-5 sm:gap-3.5 md:grid-cols-8 lg:grid-cols-10"
            >
                {#each filteredGroups as group (group.id)}
                    {@const groupStatus = getGroupStatus(group.id)}
                    <TactileTile
                        href={groupStatus === 'not_started'
                            ? introGroup.url(group.id)
                            : showGroup.url(group.id)}
                        variant={statusVariants[groupStatus]}
                        title={t('ui.level_detail.card_aria', {
                            number: group.sequence,
                            status: getStatusLabel(groupStatus),
                        })}
                        aria-label={t('ui.level_detail.card_aria', {
                            number: group.sequence,
                            status: getStatusLabel(groupStatus),
                        })}
                        class="group aspect-square flex-col p-2 sm:p-3"
                    >
                        <!-- Status Micro Icon -->
                        <div
                            class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2"
                        >
                            {#if groupStatus === 'completed'}
                                <CheckCircle2
                                    class="size-3.5 text-emerald-600 sm:size-4 dark:text-emerald-400"
                                />
                            {:else if groupStatus === 'ready'}
                                <Zap
                                    class="size-3.5 fill-blue-500/20 text-blue-600 sm:size-4 dark:text-blue-400"
                                />
                            {:else if groupStatus === 'cooldown'}
                                <Clock
                                    class="size-3.5 text-amber-600 sm:size-4 dark:text-amber-400"
                                />
                            {:else}
                                <Circle
                                    class="size-3.5 stroke-[1.5] text-slate-300 sm:size-4 dark:text-slate-600"
                                />
                            {/if}
                        </div>

                        <!-- Center Sequence Number -->
                        <span
                            class="font-sans text-base font-bold tracking-tight tabular-nums transition-transform group-hover:scale-110 sm:text-xl"
                        >
                            {group.sequence}
                        </span>
                    </TactileTile>
                {/each}
            </div>
        {/if}
    </section>
</main>
