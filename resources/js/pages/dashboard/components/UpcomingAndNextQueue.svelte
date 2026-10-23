<script lang="ts">
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Hourglass from '@lucide/svelte/icons/hourglass';
    import Info from '@lucide/svelte/icons/info';
    import Sparkles from '@lucide/svelte/icons/sparkles';
    import { onMount } from 'svelte';
    import { t } from '@/lib/i18n';
    import type { RecommendedGroupItem, UpcomingReviewItem } from '@/types';

    let {
        recommendedNextGroup = null,
        upcomingReviews = [],
    }: {
        recommendedNextGroup?: RecommendedGroupItem | null;
        upcomingReviews?: UpcomingReviewItem[];
    } = $props();

    let nowTimestamp = $state(Date.now());

    onMount(() => {
        const timer = setInterval(() => {
            nowTimestamp = Date.now();
        }, 1000);

        return () => {
            clearInterval(timer);
        };
    });

    function formatCountdown(isoString: string): string {
        const target = new Date(isoString).getTime();
        const diffMs = target - nowTimestamp;

        if (diffMs <= 0) {
            return '已可複習';
        }

        const totalSecs = Math.floor(diffMs / 1000);
        const hours = Math.floor(totalSecs / 3600);
        const mins = Math.floor((totalSecs % 3600) / 60);
        const secs = totalSecs % 60;

        const pad = (n: number) => n.toString().padStart(2, '0');
        return `倒數 ${pad(hours)}:${pad(mins)}:${pad(secs)}`;
    }
</script>

<div class="flex flex-col gap-5">
    <!-- Top Card: 推薦新進度 -->
    <div
        class="bg-blue-50/70 border-blue-200 text-card-foreground flex flex-col justify-between rounded-xl border p-5 shadow-2xs dark:border-blue-900/60 dark:bg-blue-950/30"
    >
        <div>
            <div class="mb-2 flex items-center justify-between">
                <span
                    class="rounded bg-blue-100 px-2.5 py-1 text-sm font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    {t('ui.dashboard.action_center.recommended_new')}
                </span>
                <Sparkles class="size-4 text-blue-600 dark:text-blue-400" />
            </div>

            <h3 class="text-foreground mb-1.5 text-lg font-bold">
                {recommendedNextGroup?.group_title ?? '全部進度已開啟'}
            </h3>

            <p class="text-muted-foreground mb-4 text-sm leading-relaxed">
                {t('ui.dashboard.action_center.recommended_desc')}
            </p>
        </div>

        {#if recommendedNextGroup}
            <a
                href={recommendedNextGroup.start_url}
                class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white shadow-2xs transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
                <span>{t('ui.dashboard.action_center.start_stage_1')}</span>
                <ArrowRight class="size-4" />
            </a>
        {/if}
    </div>

    <!-- Bottom Card: 即將解鎖預告 -->
    <div
        class="bg-card text-card-foreground border-border flex flex-1 flex-col justify-between rounded-xl border p-5 shadow-2xs"
    >
        <div>
            <div
                class="border-border/60 mb-3 flex items-center justify-between border-b pb-2"
            >
                <h3
                    class="text-foreground flex items-center gap-1.5 text-base font-bold"
                >
                    <Hourglass class="size-4 text-amber-600 dark:text-amber-400" />
                    <span>{t('ui.dashboard.action_center.upcoming_title')}</span>
                </h3>
            </div>

            {#if upcomingReviews.length === 0}
                <div class="py-6 text-center text-sm text-muted-foreground">
                    目前無進行中的冷卻倒數群組
                </div>
            {:else}
                <div class="space-y-3">
                    {#each upcomingReviews as review (review.group_id)}
                        <div
                            class="bg-muted/40 border-border flex items-center justify-between rounded-lg border p-2.5"
                        >
                            <div>
                                <div class="text-foreground text-sm font-semibold">
                                    {review.group_title}
                                </div>
                                <div class="text-muted-foreground text-sm">
                                    {review.stage_label}
                                </div>
                            </div>

                            <span
                                class="border-amber-200/60 bg-amber-50 text-amber-700 font-mono text-sm font-medium rounded px-2.5 py-1 border dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300"
                            >
                                {formatCountdown(review.next_review_at)}
                            </span>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>

        <div class="border-border/60 mt-4 border-t pt-3">
            <span class="text-muted-foreground flex items-center gap-1.5 text-sm">
                <Info class="size-4 shrink-0 text-muted-foreground/70" />
                <span>{t('ui.dashboard.action_center.upcoming_hint')}</span>
            </span>
        </div>
    </div>
</div>
