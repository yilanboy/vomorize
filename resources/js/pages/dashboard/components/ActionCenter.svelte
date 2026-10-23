<script lang="ts">
    import { t } from '@/lib/i18n';
    import type { DashboardStats } from '@/types';
    import DueReviewQueue from './DueReviewQueue.svelte';
    import OnboardingActionCard from './OnboardingActionCard.svelte';
    import UpcomingAndNextQueue from './UpcomingAndNextQueue.svelte';

    let {
        isEmptyState = false,
        actionCenter,
    }: {
        isEmptyState?: boolean;
        actionCenter: DashboardStats['action_center'];
    } = $props();
</script>

<section
    id="action-center"
    aria-label={t('ui.dashboard.action_center.title')}
    class="scroll-mt-6"
>
    {#if isEmptyState}
        <OnboardingActionCard
            recommendedNextGroup={actionCenter.recommended_next_group}
        />
    {:else}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <DueReviewQueue
                    dueReviews={actionCenter.due_reviews}
                    recommendedNextGroup={actionCenter.recommended_next_group}
                />
            </div>

            <div class="lg:col-span-1">
                <UpcomingAndNextQueue
                    recommendedNextGroup={actionCenter.recommended_next_group}
                    upcomingReviews={actionCenter.upcoming_reviews}
                />
            </div>
        </div>
    {/if}
</section>
