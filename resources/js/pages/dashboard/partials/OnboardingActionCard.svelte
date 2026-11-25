<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import { t } from '@/lib/i18n';
    import { index as levelsIndex } from '@/routes/levels';
    import type { RecommendedGroupItem } from '@/types';

    let {
        recommendedNextGroup = null,
    }: {
        recommendedNextGroup?: RecommendedGroupItem | null;
    } = $props();

    const startUrl = $derived(
        recommendedNextGroup?.start_url ?? levelsIndex.url(),
    );
</script>

<div
    class="bg-card text-card-foreground border-border rounded-xl border p-6 shadow-2xs sm:p-8"
>
    <h2 class="text-foreground text-2xl font-bold tracking-tight">
        {t('ui.dashboard.onboarding.title')}
    </h2>

    <p class="text-muted-foreground mt-2 max-w-[65ch] text-sm leading-relaxed">
        {t('ui.dashboard.onboarding.desc')}
    </p>

    <div class="mt-6 flex flex-wrap items-center gap-4">
        <Link
            href={startUrl}
            class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-2xs transition-[background-color,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98] motion-reduce:transition-none dark:bg-blue-600 dark:hover:bg-blue-500"
        >
            <span>{t('ui.dashboard.onboarding.start_cta')}</span>
            <ArrowRight class="size-4 shrink-0" />
        </Link>

        <span class="text-muted-foreground text-sm">
            {t('ui.dashboard.onboarding.recommended_prefix')}
            <span aria-hidden="true">·</span>
            <span class="text-foreground font-medium">
                {recommendedNextGroup?.group_title ??
                    t('ui.dashboard.onboarding.recommended_group')}
            </span>
        </span>
    </div>

    <div
        class="border-border/80 mt-8 grid grid-cols-1 gap-6 border-t pt-6 sm:grid-cols-3"
    >
        <div class="space-y-1">
            <div class="flex items-baseline gap-2">
                <span
                    class="text-sm font-bold text-blue-600 tabular-nums dark:text-blue-400"
                >
                    1.
                </span>
                <h3 class="text-foreground text-sm font-semibold">
                    {t('ui.dashboard.onboarding.step_1_title')}
                </h3>
            </div>
            <p class="text-muted-foreground text-sm leading-relaxed">
                {t('ui.dashboard.onboarding.step_1_desc')}
            </p>
        </div>

        <div class="space-y-1">
            <div class="flex items-baseline gap-2">
                <span
                    class="text-sm font-bold text-blue-600 tabular-nums dark:text-blue-400"
                >
                    2.
                </span>
                <h3 class="text-foreground text-sm font-semibold">
                    {t('ui.dashboard.onboarding.step_2_title')}
                </h3>
            </div>
            <p class="text-muted-foreground text-sm leading-relaxed">
                {t('ui.dashboard.onboarding.step_2_desc')}
            </p>
        </div>

        <div class="space-y-1">
            <div class="flex items-baseline gap-2">
                <span
                    class="text-sm font-bold text-blue-600 tabular-nums dark:text-blue-400"
                >
                    3.
                </span>
                <h3 class="text-foreground text-sm font-semibold">
                    {t('ui.dashboard.onboarding.step_3_title')}
                </h3>
            </div>
            <p class="text-muted-foreground text-sm leading-relaxed">
                {t('ui.dashboard.onboarding.step_3_desc')}
            </p>
        </div>
    </div>
</div>
