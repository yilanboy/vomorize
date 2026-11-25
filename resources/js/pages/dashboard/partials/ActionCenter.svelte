<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import { t } from '@/lib/i18n';
    import { index as levelsIndex } from '@/routes/levels';
    import type { DashboardStats } from '@/types';
    import DueReviewQueue from './DueReviewQueue.svelte';
    import OnboardingActionCard from './OnboardingActionCard.svelte';
    import RecommendedNextCard from './RecommendedNextCard.svelte';

    let {
        isEmptyState = false,
        actionCenter,
    }: {
        isEmptyState?: boolean;
        actionCenter: DashboardStats['action_center'];
    } = $props();

    const hasDueReviews = $derived(actionCenter.due_reviews.length > 0);
    const recommendedGroup = $derived(actionCenter.recommended_next_group);
</script>

<section
    id="action-center"
    aria-label={t('ui.dashboard.action_center.title')}
    class="scroll-mt-6"
>
    {#if isEmptyState}
        <OnboardingActionCard recommendedNextGroup={recommendedGroup} />
    {:else if hasDueReviews}
        {#if recommendedGroup}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <DueReviewQueue dueReviews={actionCenter.due_reviews} />
                </div>

                <div class="lg:col-span-4">
                    <RecommendedNextCard
                        recommendedNextGroup={recommendedGroup}
                    />
                </div>
            </div>
        {:else}
            <DueReviewQueue dueReviews={actionCenter.due_reviews} />
        {/if}
    {:else}
        <div
            class="bg-card text-card-foreground border-border flex flex-col justify-between gap-6 rounded-xl border p-6 shadow-2xs sm:flex-row sm:items-center sm:p-8"
        >
            <div class="flex items-start gap-4">
                <CheckCircle2
                    class="mt-0.5 size-6 shrink-0 text-emerald-600 dark:text-emerald-400"
                    aria-hidden="true"
                />
                <div class="space-y-1">
                    <h2 class="text-foreground text-base font-bold">
                        {t('ui.dashboard.action_center.due_cleared_title')}
                    </h2>
                    <p
                        class="text-muted-foreground max-w-[65ch] text-sm leading-relaxed"
                    >
                        {recommendedGroup
                            ? t('ui.dashboard.action_center.due_cleared_desc')
                            : t(
                                  'ui.dashboard.action_center.due_cleared_all_started',
                              )}
                    </p>
                </div>
            </div>

            {#if recommendedGroup}
                <Link
                    href={recommendedGroup.start_url}
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-2xs transition-[background-color,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98] motion-reduce:transition-none dark:bg-blue-600 dark:hover:bg-blue-500"
                >
                    <span>
                        {t('ui.dashboard.action_center.start_group', {
                            group: recommendedGroup.group_title,
                        })}
                    </span>
                    <ArrowRight class="size-4 shrink-0" />
                </Link>
            {:else}
                <Link
                    href={levelsIndex.url()}
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-2xs transition-[background-color,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98] motion-reduce:transition-none dark:bg-blue-600 dark:hover:bg-blue-500"
                >
                    <span>{t('ui.dashboard.browse_levels')}</span>
                    <ArrowRight class="size-4 shrink-0" />
                </Link>
            {/if}
        </div>
    {/if}
</section>
