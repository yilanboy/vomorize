<script lang="ts">
    import Hourglass from '@lucide/svelte/icons/hourglass';
    import Info from '@lucide/svelte/icons/info';
    import { onMount } from 'svelte';
    import { t } from '@/lib/i18n';
    import type { UpcomingReviewItem } from '@/types';

    let {
        upcomingReviews = [],
    }: {
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
            return t('ui.dashboard.action_center.ready_to_review');
        }

        const totalSecs = Math.floor(diffMs / 1000);
        const hours = Math.floor(totalSecs / 3600);
        const mins = Math.floor((totalSecs % 3600) / 60);
        const secs = totalSecs % 60;

        const pad = (n: number) => n.toString().padStart(2, '0');
        return t('ui.dashboard.action_center.countdown_format', {
            time: `${pad(hours)}:${pad(mins)}:${pad(secs)}`,
        });
    }
</script>

<div
    class="bg-card text-card-foreground border-border rounded-xl border p-6 shadow-2xs"
>
    <div
        class="border-border/60 mb-4 flex flex-col justify-between gap-2 border-b pb-3 sm:flex-row sm:items-center"
    >
        <h3 class="text-foreground flex items-center gap-2 text-base font-bold">
            <Hourglass class="size-4 text-amber-600 dark:text-amber-400" />
            <span>{t('ui.dashboard.action_center.upcoming_title')}</span>
        </h3>
        <span class="text-muted-foreground flex items-center gap-1.5 text-sm">
            <Info class="text-muted-foreground/70 size-4 shrink-0" />
            <span>{t('ui.dashboard.action_center.upcoming_hint')}</span>
        </span>
    </div>

    {#if upcomingReviews.length === 0}
        <div class="text-muted-foreground py-4 text-center text-sm">
            {t('ui.dashboard.action_center.upcoming_empty')}
        </div>
    {:else}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            {#each upcomingReviews as review (review.group_id)}
                <div
                    class="bg-muted/40 border-border flex items-center justify-between rounded-lg border p-3.5"
                >
                    <div class="space-y-0.5">
                        <div class="text-foreground text-sm font-semibold">
                            {review.group_title}
                        </div>
                        <div class="text-muted-foreground text-sm">
                            {review.stage_label}
                        </div>
                    </div>

                    <span
                        class="rounded border border-amber-200/60 bg-amber-50 px-2.5 py-1 font-mono text-sm font-medium text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300"
                    >
                        {formatCountdown(review.next_review_at)}
                    </span>
                </div>
            {/each}
        </div>
    {/if}
</div>
