<script lang="ts">
    import { t } from '@/lib/i18n';
    import type { DashboardStats } from '@/types';
    import DueReviewQueue from './DueReviewQueue.svelte';
    import OnboardingActionCard from './OnboardingActionCard.svelte';
    import RecommendedNextCard from './RecommendedNextCard.svelte';
    import UpcomingCooldownCard from './UpcomingCooldownCard.svelte';

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
        <div class="space-y-6">
            <!-- Row 1: 立即可複習佇列 (左 2/3) + 推薦新進度 (右 1/3) -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <DueReviewQueue
                        dueReviews={actionCenter.due_reviews}
                        recommendedNextGroup={actionCenter.recommended_next_group}
                    />
                </div>

                <div class="lg:col-span-1">
                    <RecommendedNextCard
                        recommendedNextGroup={actionCenter.recommended_next_group}
                    />
                </div>
            </div>

            <!-- Row 2: 即將解鎖預告 (冷卻中) - 橫向 3 欄展示 -->
            {#if actionCenter.upcoming_reviews.length > 0}
                <UpcomingCooldownCard
                    upcomingReviews={actionCenter.upcoming_reviews}
                />
            {/if}
        </div>
    {/if}
</section>
