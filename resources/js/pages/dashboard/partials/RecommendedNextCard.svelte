<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import { t } from '@/lib/i18n';
    import type { RecommendedGroupItem } from '@/types';

    let {
        recommendedNextGroup,
    }: {
        recommendedNextGroup: RecommendedGroupItem;
    } = $props();
</script>

<div
    class="bg-muted/35 border-border flex h-full flex-col justify-between rounded-xl border p-5 sm:p-6"
>
    <div>
        <h2 class="text-muted-foreground text-sm font-medium">
            {t('ui.dashboard.action_center.recommended_new')}
        </h2>

        <p class="text-foreground mt-2 text-base font-semibold">
            {recommendedNextGroup.group_title}
        </p>
    </div>

    <Link
        href={recommendedNextGroup.start_url}
        class="bg-card border-border text-foreground mt-5 inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border px-4 text-sm font-semibold transition-[background-color,border-color,color,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] hover:border-blue-500/60 hover:bg-blue-600/8 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98] motion-reduce:transition-none dark:hover:border-blue-400/60 dark:hover:bg-blue-400/12 dark:hover:text-blue-400"
    >
        <span>{t('ui.dashboard.action_center.start_learning')}</span>
        <ArrowRight class="size-4 shrink-0" />
    </Link>
</div>
