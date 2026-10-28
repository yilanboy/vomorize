<script lang="ts">
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Layers from '@lucide/svelte/icons/layers';
    import Sparkles from '@lucide/svelte/icons/sparkles';
    import { t } from '@/lib/i18n';
    import type { DashboardStats } from '@/types';

    let {
        levelProgress,
    }: {
        levelProgress: DashboardStats['level_progress'];
    } = $props();
</script>

<section aria-label={t('ui.dashboard.levels.title')}>
    <!-- Section Header -->
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <Layers class="size-5 text-blue-600 dark:text-blue-400" />
            <h2 class="text-foreground text-lg font-bold">
                {t('ui.dashboard.levels.title')}
            </h2>
        </div>
        <a
            href="/levels"
            class="group inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
        >
            <span>{t('ui.dashboard.levels.view_all')}</span>
            <ArrowRight
                class="size-4 transition-transform group-hover:translate-x-0.5"
            />
        </a>
    </div>

    <!-- 8-Card Grid: Levels 1-7 + Overall Summary -->
    <div
        class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
    >
        {#each levelProgress.levels as level (level.id)}
            <div
                class="bg-card text-card-foreground border-border flex flex-col justify-between rounded-xl border p-5 shadow-2xs transition-all hover:border-blue-400 hover:shadow-xs dark:hover:border-blue-500"
            >
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <span
                            class="rounded bg-blue-50 px-2.5 py-1 text-sm font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                        >
                            {level.name}
                        </span>
                        <span
                            class={{
                                'text-sm font-bold': true,
                                'text-emerald-600 dark:text-emerald-400':
                                    level.mastery_percentage > 0,
                                'text-muted-foreground':
                                    level.mastery_percentage <= 0,
                            }}
                        >
                            {t('ui.dashboard.levels.mastery_tag', {
                                rate: level.mastery_percentage,
                            })}
                        </span>
                    </div>

                    <h3 class="text-foreground mb-1 text-base font-bold">
                        {level.subtitle}
                    </h3>

                    <p class="text-muted-foreground mb-3 text-sm">
                        {t('ui.dashboard.levels.level_stats', {
                            mastered: level.mastered_groups_count,
                            learning: level.learning_groups_count,
                            total: level.total_groups_count,
                            words: level.total_words_count,
                        })}
                    </p>

                    <!-- Progress bar -->
                    <div
                        class="bg-muted mb-4 h-2 w-full overflow-hidden rounded-full"
                    >
                        <div
                            class="h-2 rounded-full bg-emerald-600 transition-all dark:bg-emerald-500"
                            style="width: {level.mastery_percentage}%"
                        ></div>
                    </div>
                </div>

                <a
                    href={level.url}
                    class="bg-muted/40 text-foreground border-border inline-flex h-11 w-full items-center justify-center gap-1 rounded-lg border text-sm font-semibold transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:hover:border-blue-800 dark:hover:bg-blue-950/60 dark:hover:text-blue-300"
                >
                    <span>{t('ui.dashboard.levels.enter_level')}</span>
                    <ArrowRight class="size-4" />
                </a>
            </div>
        {/each}

        <!-- Card 8: 全庫掌握進度 -->
        <div
            class="text-card-foreground flex flex-col justify-between rounded-xl border border-blue-200 bg-blue-50/70 p-5 shadow-2xs dark:border-blue-900/60 dark:bg-blue-950/30"
        >
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="rounded bg-blue-100 px-2.5 py-1 text-sm font-bold text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                    >
                        {t('ui.dashboard.levels.overall_title')}
                    </span>
                    <span
                        class="text-sm font-bold text-blue-700 dark:text-blue-400"
                    >
                        {t('ui.dashboard.levels.overall_rate', {
                            rate: levelProgress.overall.mastery_percentage,
                        })}
                    </span>
                </div>

                <h3 class="text-foreground mb-1 text-base font-bold">
                    {t('ui.dashboard.levels.overall_mastered', {
                        count: levelProgress.overall.mastered_groups_count,
                    })}
                </h3>

                <p class="text-muted-foreground mb-3 text-sm">
                    {t('ui.dashboard.levels.overall_stats', {
                        groups: levelProgress.overall.total_groups_count,
                        words: levelProgress.overall.total_words_count,
                        mastered_words:
                            levelProgress.overall.mastered_words_count,
                    })}
                </p>

                <div
                    class="mb-4 h-2 w-full overflow-hidden rounded-full bg-blue-100 dark:bg-blue-950"
                >
                    <div
                        class="h-2 rounded-full bg-blue-600 transition-all dark:bg-blue-500"
                        style="width: {levelProgress.overall
                            .mastery_percentage}%"
                    ></div>
                </div>
            </div>

            <a
                href="/levels"
                class="inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-lg bg-blue-600 text-sm font-semibold text-white shadow-2xs transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
                <Sparkles class="size-4" />
                <span>
                    {levelProgress.overall.mastery_percentage > 0
                        ? t('ui.dashboard.levels.smart_review')
                        : t('ui.dashboard.levels.smart_recommend')}
                </span>
            </a>
        </div>
    </div>
</section>
