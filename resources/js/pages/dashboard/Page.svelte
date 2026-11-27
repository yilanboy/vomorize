<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import AppHead from '@/components/AppHead.svelte';
    import { t } from '@/lib/i18n';
    import { index as levelsIndex } from '@/routes/levels';
    import type { DashboardStats } from '@/types';
    import ActionCenter from './partials/ActionCenter.svelte';
    import KpiSummaryCards from './partials/KpiSummaryCards.svelte';

    let {
        stats,
    }: {
        stats: DashboardStats;
    } = $props();

    const user = $derived(page.props.auth?.user);
    const userName = $derived(user?.name || '');
</script>

<AppHead title={t('ui.dashboard.title')} />

<div
    class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8"
>
    <header
        class="border-border/80 flex flex-col justify-between gap-3 border-b pb-5 sm:flex-row sm:items-end"
    >
        <div>
            <h1
                class="text-foreground text-2xl font-bold tracking-tight sm:text-3xl"
            >
                {t('ui.dashboard.title')}
            </h1>
            {#if !stats.is_empty_state && userName}
                <p class="text-muted-foreground mt-1 text-sm">
                    {t('ui.dashboard.welcome_back', { name: userName })}
                </p>
            {/if}
        </div>

        <Link
            href={levelsIndex.url()}
            class="group text-muted-foreground hover:text-foreground inline-flex w-fit items-center gap-1.5 rounded-md py-1 text-sm font-medium transition-colors duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none"
        >
            <span>{t('ui.dashboard.browse_levels')}</span>
            <ArrowRight
                class="size-4 transition-transform duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-0.5 motion-reduce:transition-none"
            />
        </Link>
    </header>

    {#if !stats.is_empty_state}
        <KpiSummaryCards kpis={stats.kpis} />
    {/if}

    <ActionCenter
        isEmptyState={stats.is_empty_state}
        actionCenter={stats.action_center}
    />
</div>
