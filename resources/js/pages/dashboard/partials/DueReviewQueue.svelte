<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import { t } from '@/lib/i18n';
    import type { DueReviewItem } from '@/types';

    let {
        dueReviews = [],
    }: {
        dueReviews?: DueReviewItem[];
    } = $props();
</script>

<div
    class="bg-card text-card-foreground border-border flex h-full flex-col overflow-hidden rounded-xl border shadow-2xs"
>
    <div
        class="border-border/80 flex items-baseline justify-between gap-3 border-b px-5 py-4 sm:px-6"
    >
        <h2 class="text-foreground text-base font-bold">
            {t('ui.dashboard.action_center.due_title')}
        </h2>
        <span class="text-muted-foreground text-sm font-medium tabular-nums">
            {t('ui.dashboard.action_center.due_count', {
                count: dueReviews.length,
            })}
        </span>
    </div>

    <div class="divide-border/70 divide-y">
        {#each dueReviews as review (review.group_id)}
            <div
                class="hover:bg-muted/35 flex flex-col justify-between gap-4 px-5 py-4 transition-[background-color] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none sm:flex-row sm:items-center sm:px-6"
            >
                <div class="min-w-0 space-y-1">
                    <div class="text-foreground text-base font-semibold">
                        {review.group_title}
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm"
                    >
                        <span
                            class={{
                                'font-medium': true,
                                'text-red-600 dark:text-red-400':
                                    review.is_failed,
                                'text-emerald-600 dark:text-emerald-400':
                                    !review.is_failed &&
                                    review.badge_variant === 'success',
                                'text-blue-600 dark:text-blue-400':
                                    !review.is_failed &&
                                    review.badge_variant !== 'success',
                            }}
                        >
                            {review.badge_text}
                        </span>

                        {#if review.is_failed && review.last_score !== null}
                            <span
                                class="text-muted-foreground"
                                aria-hidden="true">·</span
                            >
                            <span
                                class="text-red-600 tabular-nums dark:text-red-400"
                            >
                                {t(
                                    'ui.dashboard.action_center.last_score_failed',
                                    { score: review.last_score },
                                )}
                            </span>
                        {:else if review.target_stage >= 6}
                            <span
                                class="text-muted-foreground"
                                aria-hidden="true">·</span
                            >
                            <span
                                class="text-emerald-600 dark:text-emerald-400"
                            >
                                {t(
                                    'ui.dashboard.action_center.hint_permanent_memory',
                                )}
                            </span>
                        {:else if review.last_score !== null}
                            <span
                                class="text-muted-foreground"
                                aria-hidden="true">·</span
                            >
                            <span class="text-muted-foreground tabular-nums">
                                {t(
                                    'ui.dashboard.action_center.last_score_short',
                                    { score: review.last_score },
                                )}
                            </span>
                        {/if}
                    </div>
                </div>

                {#if review.is_failed}
                    <Link
                        href={review.review_url}
                        class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 self-start rounded-lg bg-red-600 px-4 text-sm font-semibold text-white shadow-2xs transition-[background-color,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] hover:bg-red-700 focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98] motion-reduce:transition-none sm:self-center dark:bg-red-600 dark:hover:bg-red-500"
                    >
                        <span>
                            {t('ui.dashboard.action_center.retake_quiz')}
                        </span>
                        <RotateCcw class="size-4 shrink-0" />
                    </Link>
                {:else}
                    <Link
                        href={review.review_url}
                        class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 self-start rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-2xs transition-[background-color,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98] motion-reduce:transition-none sm:self-center dark:bg-blue-600 dark:hover:bg-blue-500"
                    >
                        <span>
                            {t('ui.dashboard.action_center.start_quiz')}
                        </span>
                        <ArrowRight class="size-4 shrink-0" />
                    </Link>
                {/if}
            </div>
        {/each}
    </div>
</div>
