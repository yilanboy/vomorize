<script lang="ts">
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import ListFilter from '@lucide/svelte/icons/list-filter';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Sparkles from '@lucide/svelte/icons/sparkles';
    import TriangleAlert from '@lucide/svelte/icons/triangle-alert';
    import { t } from '@/lib/i18n';
    import type { DueReviewItem, RecommendedGroupItem } from '@/types';

    let {
        dueReviews = [],
        recommendedNextGroup = null,
    }: {
        dueReviews?: DueReviewItem[];
        recommendedNextGroup?: RecommendedGroupItem | null;
    } = $props();
</script>

<div
    class="bg-card text-card-foreground border-border flex h-full flex-col justify-between rounded-xl border p-6 shadow-2xs"
>
    <div>
        <!-- Header -->
        <div
            class="border-border/60 mb-5 flex items-center justify-between border-b pb-3"
        >
        <div class="flex items-center gap-2.5">
            <ListFilter class="size-5 text-blue-600 dark:text-blue-400" />
            <h2 class="text-foreground text-lg font-bold">
                {t('ui.dashboard.action_center.due_title')}
            </h2>
        </div>
        <span
            class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300"
        >
            {t('ui.dashboard.action_center.due_badge', {
                count: dueReviews.length,
            })}
        </span>
    </div>

    <!-- Cleared empty state inside queue -->
    {#if dueReviews.length === 0}
        <div class="flex flex-col items-center justify-center py-10 text-center">
            <div
                class="flex size-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
            >
                <CheckCircle2 class="size-8" />
            </div>
            <h3 class="text-foreground mt-4 text-lg font-bold">
                {t('ui.dashboard.action_center.due_cleared_title')}
            </h3>
            <p class="text-muted-foreground mt-1.5 max-w-md text-sm leading-relaxed">
                {t('ui.dashboard.action_center.due_cleared_desc')}
            </p>
            {#if recommendedNextGroup}
                <a
                    href={recommendedNextGroup.start_url}
                    class="mt-6 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-2xs transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
                >
                    <Sparkles class="size-4" />
                    <span>{t('ui.dashboard.action_center.advance_new_group')}</span>
                </a>
            {/if}
        </div>
    {:else}
        <!-- Review Items List -->
        <div class="divide-border/60 divide-y">
            {#each dueReviews as review (review.group_id)}
                <div
                    class="flex flex-col justify-between gap-4 py-4 first:pt-0 last:pb-0 hover:bg-muted/40 sm:flex-row sm:items-center rounded-lg p-2.5 transition"
                >
                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="text-foreground text-base font-semibold">
                                {review.group_title}
                            </span>

                            {#if review.badge_variant === 'warning'}
                                <span
                                    class="inline-flex items-center gap-1 rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-sm font-medium text-red-700 dark:border-red-900/60 dark:bg-red-950/50 dark:text-red-400"
                                >
                                    <TriangleAlert class="size-3.5" />
                                    <span>{review.badge_text}</span>
                                </span>
                            {:else if review.badge_variant === 'success'}
                                <span
                                    class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/50 dark:text-emerald-400"
                                >
                                    <CheckCircle2 class="size-3.5" />
                                    <span>{review.badge_text}</span>
                                </span>
                            {:else}
                                <span
                                    class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-sm font-medium text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/50 dark:text-blue-400"
                                >
                                    <span>{review.badge_text}</span>
                                </span>
                            {/if}
                        </div>

                        <div
                            class="text-muted-foreground flex flex-wrap items-center gap-3 text-sm"
                        >
                            <span>{t('ui.dashboard.action_center.words_count', { count: review.words_count })}</span>
                            <span>·</span>
                            <span>
                                {t('ui.dashboard.action_center.last_score_label')}<strong class="text-foreground font-medium"
                                    >{review.last_score !== null ? t('ui.dashboard.action_center.score_value', { score: review.last_score }) : t('ui.dashboard.action_center.no_score')}</strong
                                >
                            </span>
                            <span>·</span>
                            <span
                                class={review.badge_variant === 'warning'
                                    ? 'font-medium text-red-600 dark:text-red-400'
                                    : 'font-medium text-emerald-600 dark:text-emerald-400'}
                            >
                                {review.status_hint}
                            </span>
                        </div>
                    </div>

                    {#if review.badge_variant === 'warning'}
                        <a
                            href={review.review_url}
                            class="inline-flex h-11 shrink-0 items-center justify-center gap-1.5 self-start rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-2xs transition hover:bg-red-700 sm:self-center dark:bg-red-600 dark:hover:bg-red-500"
                        >
                            <span>{t('ui.dashboard.action_center.retake_quiz')}</span>
                            <RotateCcw class="size-4" />
                        </a>
                    {:else}
                        <a
                            href={review.review_url}
                            class="inline-flex h-11 shrink-0 items-center justify-center gap-1.5 self-start rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-2xs transition hover:bg-blue-700 sm:self-center dark:bg-blue-600 dark:hover:bg-blue-500"
                        >
                            <span>{t('ui.dashboard.action_center.start_quiz')}</span>
                            <ArrowRight class="size-4" />
                        </a>
                    {/if}
                </div>
            {/each}
        </div>
    {/if}
    </div>
</div>
