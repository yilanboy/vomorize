<script lang="ts">
    import { page, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { t } from '@/lib/i18n';
    import type { BreadcrumbItem, DashboardStats } from '@/types';
    import ActionCenter from './dashboard/components/ActionCenter.svelte';
    import DashboardHeroHeader from './dashboard/components/DashboardHeroHeader.svelte';
    import KpiSummaryCards from './dashboard/components/KpiSummaryCards.svelte';
    import LevelMasteryGrid from './dashboard/components/LevelMasteryGrid.svelte';
    import SrsStageBreakdown from './dashboard/components/SrsStageBreakdown.svelte';

    let {
        stats,
    }: {
        stats: DashboardStats;
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('ui.dashboard.breadcrumb'),
            href: '/dashboard',
        },
    ];

    setLayoutProps({
        breadcrumbs,
    });

    const user = $derived(page.props.auth?.user);
    const userName = $derived(user?.name || '');
</script>

<AppHead title={t('ui.dashboard.title')} />

<div
    class="mx-auto flex w-full max-w-7xl flex-1 flex-col space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8"
>
    <!-- Section 1: Dashboard Hero Header -->
    <DashboardHeroHeader
        isEmptyState={stats.is_empty_state}
        {userName}
        dueCount={stats.kpis.due_groups.count}
        recommendedUrl={stats.action_center.recommended_next_group?.start_url}
    />

    <!-- Section 2: Top 4 KPI Summary Cards -->
    <KpiSummaryCards kpis={stats.kpis} />

    <!-- Section 3: Action Center (Onboarding or Due/Upcoming Queues) -->
    <ActionCenter
        isEmptyState={stats.is_empty_state}
        actionCenter={stats.action_center}
    />

    <!-- Section 4: SRS Stage Breakdown Visualization -->
    <SrsStageBreakdown srsDistribution={stats.srs_distribution} />

    <!-- Section 5: Level Mastery Grid (1-7 + Overall Summary) -->
    <LevelMasteryGrid levelProgress={stats.level_progress} />
</div>
