<script lang="ts">
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Compass from '@lucide/svelte/icons/compass';
    import Sparkles from '@lucide/svelte/icons/sparkles';
    import { t } from '@/lib/i18n';
    import type { RecommendedGroupItem } from '@/types';

    let {
        recommendedNextGroup = null,
    }: {
        recommendedNextGroup?: RecommendedGroupItem | null;
    } = $props();

    const startUrl = $derived(recommendedNextGroup?.start_url ?? '/groups/1');
</script>

<div
    class="bg-card rounded-2xl border border-blue-200 p-6 shadow-2xs sm:p-8 dark:border-blue-900/60"
>
    <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-12">
        <!-- Left 7 columns -->
        <div class="space-y-4 lg:col-span-7">
            <div
                class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300"
            >
                <Sparkles class="size-4 text-blue-600 dark:text-blue-400" />
                <span>{t('ui.dashboard.onboarding.badge')}</span>
            </div>

            <h2
                class="text-foreground text-2xl font-bold tracking-tight sm:text-3xl"
            >
                {t('ui.dashboard.onboarding.title')}
            </h2>

            <p class="text-muted-foreground text-base leading-relaxed">
                {t('ui.dashboard.onboarding.desc')}
            </p>

            <div
                class="flex flex-col justify-between gap-4 rounded-xl border border-blue-200 bg-blue-50/70 p-4 sm:flex-row sm:items-center dark:border-blue-900/40 dark:bg-blue-950/30"
            >
                <div>
                    <span
                        class="text-sm font-semibold text-blue-800 dark:text-blue-300"
                    >
                        {t('ui.dashboard.onboarding.recommended_prefix')}
                    </span>
                    <span class="text-foreground ml-1 text-sm font-bold">
                        {recommendedNextGroup?.group_title ??
                            t('ui.dashboard.onboarding.recommended_group')}
                    </span>
                    <p class="text-muted-foreground mt-0.5 text-sm">
                        {t('ui.dashboard.onboarding.recommended_hint')}
                    </p>
                </div>

                <a
                    href={startUrl}
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-2xs transition hover:bg-blue-700 hover:shadow-xs dark:bg-blue-600 dark:hover:bg-blue-500"
                >
                    <span>{t('ui.dashboard.onboarding.start_cta')}</span>
                    <ArrowRight class="size-4" />
                </a>
            </div>
        </div>

        <!-- Right 5 columns -->
        <div
            class="bg-muted/40 border-border space-y-4 rounded-xl border p-5 lg:col-span-5"
        >
            <div
                class="text-foreground border-border flex items-center gap-2 border-b pb-2 text-base font-bold"
            >
                <Compass class="size-5 text-blue-600 dark:text-blue-400" />
                <span>{t('ui.dashboard.onboarding.steps_title')}</span>
            </div>

            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                    >
                        1
                    </div>
                    <div>
                        <div class="text-foreground text-sm font-semibold">
                            {t('ui.dashboard.onboarding.step_1_title')}
                        </div>
                        <p class="text-muted-foreground mt-0.5 text-sm">
                            {t('ui.dashboard.onboarding.step_1_desc')}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div
                        class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                    >
                        2
                    </div>
                    <div>
                        <div class="text-foreground text-sm font-semibold">
                            {t('ui.dashboard.onboarding.step_2_title')}
                        </div>
                        <p class="text-muted-foreground mt-0.5 text-sm">
                            {t('ui.dashboard.onboarding.step_2_desc')}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div
                        class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                    >
                        3
                    </div>
                    <div>
                        <div class="text-foreground text-sm font-semibold">
                            {t('ui.dashboard.onboarding.step_3_title')}
                        </div>
                        <p class="text-muted-foreground mt-0.5 text-sm">
                            {t('ui.dashboard.onboarding.step_3_desc')}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
