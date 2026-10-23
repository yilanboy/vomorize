<script lang="ts">
    import Brain from '@lucide/svelte/icons/brain';
    import { t } from '@/lib/i18n';
    import type { DashboardStats } from '@/types';

    let {
        srsDistribution,
    }: {
        srsDistribution: DashboardStats['srs_distribution'];
    } = $props();
</script>

<section aria-label={t('ui.dashboard.srs.title')}>
    <div
        class="bg-card text-card-foreground border-border rounded-xl border p-6 shadow-2xs"
    >
        <!-- Header -->
        <div
            class="mb-2 flex flex-col justify-between gap-1 sm:flex-row sm:items-baseline"
        >
            <div class="flex items-center gap-2">
                <Brain class="size-5 text-blue-600 dark:text-blue-400" />
                <h2 class="text-foreground text-lg font-bold">
                    {t('ui.dashboard.srs.title')}
                </h2>
            </div>
            <p class="text-muted-foreground text-sm">
                目前已接觸
                <strong class="text-foreground font-semibold">
                    {srsDistribution.total_contacted_groups} 個群組
                </strong>
                （共 {srsDistribution.total_contacted_words} 個單字）之記憶階段推進狀態
            </p>
        </div>

        <!-- Multi-segment segmented bar -->
        <div class="my-4">
            {#if !srsDistribution.has_data}
                <div
                    class="bg-muted border-border/80 text-muted-foreground flex h-4 w-full items-center justify-center overflow-hidden rounded-full border text-sm font-medium shadow-inner"
                >
                    {t('ui.dashboard.srs.empty_bar')}
                </div>
            {:else}
                <div
                    class="bg-muted shadow-inner flex h-4 w-full overflow-hidden rounded-full"
                >
                    {#each srsDistribution.stages as stage (stage.key)}
                        {#if stage.percentage > 0}
                            <div
                                class="{stage.color_class} transition-all"
                                style="width: {stage.percentage}%"
                                title="{stage.label}: {stage.groups_count} 組 ({stage.percentage}%)"
                            ></div>
                        {/if}
                    {/each}
                </div>
            {/if}
        </div>

        <!-- 5-column breakdown grid -->
        <div class="grid grid-cols-2 gap-4 pt-2 sm:grid-cols-3 md:grid-cols-5">
            {#each srsDistribution.stages as stage (stage.key)}
                <div
                    class="bg-muted/40 border-border flex flex-col justify-between rounded-lg border p-3"
                >
                    <div class="mb-1 flex items-center gap-2">
                        <span
                            class="size-2.5 shrink-0 rounded-full {stage.groups_count > 0 ? stage.color_class : 'bg-muted-foreground/30'}"
                        ></span>
                        <span class="text-foreground text-sm font-semibold">
                            {stage.label}
                        </span>
                    </div>

                    <div class="text-foreground text-sm font-bold">
                        {stage.groups_count} 組 ({stage.words_count} 字)
                    </div>

                    <div
                        class="mt-1 text-sm {stage.is_alert && stage.groups_count > 0 ? 'font-medium text-red-600 dark:text-red-400' : 'text-muted-foreground'}"
                    >
                        {stage.description}
                    </div>
                </div>
            {/each}
        </div>
    </div>
</section>
