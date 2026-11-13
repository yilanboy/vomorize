<script lang="ts">
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Sparkles from '@lucide/svelte/icons/sparkles';
    import { t } from '@/lib/i18n';
    import type { RecommendedGroupItem } from '@/types';

    let {
        recommendedNextGroup = null,
    }: {
        recommendedNextGroup?: RecommendedGroupItem | null;
    } = $props();
</script>

<div
    class="text-card-foreground flex h-full flex-col justify-between rounded-xl border border-blue-200 bg-blue-50/70 p-6 shadow-2xs dark:border-blue-900/60 dark:bg-blue-950/30"
>
    <div>
        <div class="mb-3 flex items-center justify-between">
            <span
                class="rounded bg-blue-100 px-2.5 py-1 text-sm font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200"
            >
                {t('ui.dashboard.action_center.recommended_new')}
            </span>
            <Sparkles class="size-4 text-blue-600 dark:text-blue-400" />
        </div>

        <h3 class="text-foreground mb-2 text-lg font-bold">
            {recommendedNextGroup?.group_title ??
                t('ui.dashboard.action_center.all_started')}
        </h3>

        <p class="text-muted-foreground mb-6 text-sm leading-relaxed">
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
