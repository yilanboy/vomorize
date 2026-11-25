<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import Clock from '@lucide/svelte/icons/clock';
    import Flame from '@lucide/svelte/icons/flame';
    import { t } from '@/lib/i18n';
    import type { DashboardStats } from '@/types';

    let {
        kpis,
    }: {
        kpis: DashboardStats['kpis'];
    } = $props();
</script>

<section aria-label={t('ui.dashboard.kpis.streak')}>
    <div
        class="bg-card text-card-foreground border-border divide-border/80 grid grid-cols-1 divide-y rounded-xl border shadow-2xs sm:grid-cols-3 sm:divide-x sm:divide-y-0"
    >
        <!-- Metric 1: Streak -->
        <div class="flex flex-col justify-between gap-3 p-5 sm:p-6">
            <span class="text-muted-foreground text-sm font-medium">
                {t('ui.dashboard.kpis.streak')}
            </span>

            <div class="flex items-baseline gap-2">
                <Flame
                    class={{
                        'size-5 shrink-0 translate-y-0.5': true,
                        'fill-amber-500 text-amber-500':
                            kpis.streak.current > 0,
                        'text-muted-foreground/50': kpis.streak.current <= 0,
                    }}
                    aria-hidden="true"
                />
                <span
                    class="text-foreground text-3xl font-bold tracking-tight tabular-nums"
                >
                    {kpis.streak.current}
                </span>
                <span class="text-muted-foreground text-sm font-medium">
                    {t('ui.dashboard.kpis.unit_day')}
                </span>
            </div>

            <div
                class="text-muted-foreground flex flex-wrap items-center gap-x-2 gap-y-1 text-sm"
            >
                {#if kpis.streak.is_checked_in_today}
                    <span
                        class="inline-flex items-center gap-1 font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        <Check class="size-4 shrink-0" />
                        <span>{t('ui.dashboard.kpis.streak_checked_in')}</span>
                    </span>
                {:else}
                    <span class="inline-flex items-center gap-1">
                        <Clock class="size-4 shrink-0" />
                        <span>{t('ui.dashboard.kpis.streak_pending')}</span>
                    </span>
                {/if}
                <span aria-hidden="true">·</span>
                <span class="tabular-nums">
                    {t('ui.dashboard.kpis.streak_longest', {
                        days: kpis.streak.longest,
                    })}
                </span>
            </div>
        </div>

        <!-- Metric 2: Learning Words -->
        <div class="flex flex-col justify-between gap-3 p-5 sm:p-6">
            <span class="text-muted-foreground text-sm font-medium">
                {t('ui.dashboard.kpis.learning_words')}
            </span>

            <div class="flex items-baseline gap-1.5">
                <span
                    class="text-3xl font-bold tracking-tight text-blue-600 tabular-nums dark:text-blue-400"
                >
                    {kpis.learning_words.words_count}
                </span>
                <span class="text-muted-foreground text-sm font-medium">
                    {t('ui.dashboard.kpis.unit_word')}
                </span>
            </div>

            <p class="text-muted-foreground text-sm tabular-nums">
                {t('ui.dashboard.kpis.learning_groups', {
                    count: kpis.learning_words.groups_count,
                })}
            </p>
        </div>

        <!-- Metric 3: Mastered Words -->
        <div class="flex flex-col justify-between gap-3 p-5 sm:p-6">
            <span class="text-muted-foreground text-sm font-medium">
                {t('ui.dashboard.kpis.mastered_words')}
            </span>

            <div class="flex items-baseline gap-1.5">
                <span
                    class="text-3xl font-bold tracking-tight text-emerald-600 tabular-nums dark:text-emerald-400"
                >
                    {kpis.mastered_words.words_count}
                </span>
                <span class="text-muted-foreground text-sm font-medium">
                    {t('ui.dashboard.kpis.unit_word')}
                </span>
            </div>

            <p class="text-muted-foreground text-sm tabular-nums">
                {#if kpis.mastered_words.groups_count > 0}
                    {t('ui.dashboard.kpis.mastered_groups', {
                        count: kpis.mastered_words.groups_count,
                    })}
                {:else}
                    {t('ui.dashboard.kpis.mastered_words_zero')}
                {/if}
            </p>
        </div>
    </div>
</section>
