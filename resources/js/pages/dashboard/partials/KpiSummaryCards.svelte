<script lang="ts">
    import BookOpen from '@lucide/svelte/icons/book-open';
    import Check from '@lucide/svelte/icons/check';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import Clock from '@lucide/svelte/icons/clock';
    import Flame from '@lucide/svelte/icons/flame';
    import { t } from '@/lib/i18n';
    import type { DashboardStats } from '@/types';

    let {
        kpis,
    }: {
        kpis: DashboardStats['kpis'];
    } = $props();

    function scrollToQueue() {
        const el = document.getElementById('action-center');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>

<section aria-label={t('ui.dashboard.kpis.streak')}>
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: 連續學習天數 -->
        <div
            class="bg-card text-card-foreground border-border flex flex-col justify-between rounded-xl border p-5 shadow-2xs transition-all hover:border-slate-300 dark:hover:border-slate-700"
        >
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground text-sm font-medium">
                        {t('ui.dashboard.kpis.streak')}
                    </span>
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400"
                    >
                        <Flame class="size-5 fill-amber-500 text-amber-500" />
                    </div>
                </div>
                <div class="text-foreground mt-2 text-3xl font-bold">
                    {kpis.streak.current}
                    <span class="text-muted-foreground text-lg font-medium"
                        >{t('ui.dashboard.kpis.unit_day')}</span
                    >
                </div>
            </div>

            <div
                class="border-border/60 mt-4 flex items-center justify-between gap-2 border-t pt-3"
            >
                {#if kpis.streak.is_checked_in_today}
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-sm font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400"
                    >
                        <Check class="size-3.5" />
                        {t('ui.dashboard.kpis.streak_checked_in')}
                    </span>
                {:else}
                    <span
                        class="bg-muted text-muted-foreground inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-sm font-medium"
                    >
                        <Clock class="size-3.5" />
                        {t('ui.dashboard.kpis.streak_pending')}
                    </span>
                {/if}
                <span class="text-muted-foreground text-sm">
                    {t('ui.dashboard.kpis.streak_longest', {
                        days: kpis.streak.longest,
                    })}
                </span>
            </div>
        </div>

        <!-- Card 2: 學習中單字 -->
        <div
            class="bg-card text-card-foreground border-border flex flex-col justify-between rounded-xl border p-5 shadow-2xs transition-all hover:border-slate-300 dark:hover:border-slate-700"
        >
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground text-sm font-medium">
                        {t('ui.dashboard.kpis.learning_words')}
                    </span>
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400"
                    >
                        <BookOpen class="size-5" />
                    </div>
                </div>
                <div
                    class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400"
                >
                    {kpis.learning_words.words_count}
                    <span class="text-muted-foreground text-lg font-medium"
                        >{t('ui.dashboard.kpis.unit_word')}</span
                    >
                </div>
            </div>

            <div class="border-border/60 mt-4 border-t pt-3">
                <p
                    class="text-muted-foreground flex items-center gap-1.5 text-sm"
                >
                    <span
                        class={{
                            'size-2 rounded-full': true,
                            'bg-blue-500': kpis.learning_words.groups_count > 0,
                            'bg-muted-foreground/40':
                                kpis.learning_words.groups_count <= 0,
                        }}
                    ></span>
                    <span>
                        {t('ui.dashboard.kpis.learning_words_desc', {
                            count: kpis.learning_words.groups_count,
                        })}
                    </span>
                </p>
            </div>
        </div>

        <!-- Card 3: 已精通單字 -->
        <div
            class="bg-card text-card-foreground border-border flex flex-col justify-between rounded-xl border p-5 shadow-2xs transition-all hover:border-slate-300 dark:hover:border-slate-700"
        >
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground text-sm font-medium">
                        {t('ui.dashboard.kpis.mastered_words')}
                    </span>
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
                    >
                        <CheckCircle2 class="size-5" />
                    </div>
                </div>
                <div
                    class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400"
                >
                    {kpis.mastered_words.words_count}
                    <span class="text-muted-foreground text-lg font-medium"
                        >{t('ui.dashboard.kpis.unit_word')}</span
                    >
                </div>
            </div>

            <div class="border-border/60 mt-4 border-t pt-3">
                <p
                    class="text-muted-foreground flex items-center gap-1.5 text-sm"
                >
                    <span
                        class={{
                            'size-2 rounded-full': true,
                            'bg-emerald-500':
                                kpis.mastered_words.groups_count > 0,
                            'bg-muted-foreground/40':
                                kpis.mastered_words.groups_count <= 0,
                        }}
                    ></span>
                    <span>
                        {#if kpis.mastered_words.groups_count > 0}
                            {t('ui.dashboard.kpis.mastered_words_desc', {
                                count: kpis.mastered_words.groups_count,
                                percent: kpis.mastered_words.percentage,
                            })}
                        {:else}
                            {t('ui.dashboard.kpis.mastered_words_zero')}
                        {/if}
                    </span>
                </p>
            </div>
        </div>

        <!-- Card 4: 待複習群組 -->
        <button
            type="button"
            onclick={scrollToQueue}
            class="bg-card text-card-foreground border-border flex flex-col justify-between rounded-xl border p-5 text-left shadow-2xs transition-all hover:border-blue-400 hover:shadow-xs dark:hover:border-blue-500"
        >
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground text-sm font-medium">
                        {t('ui.dashboard.kpis.due_groups')}
                    </span>
                    <div
                        class={{
                            'flex size-10 items-center justify-center rounded-lg': true,
                            'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400':
                                kpis.due_groups.has_due,
                            'bg-muted text-muted-foreground':
                                !kpis.due_groups.has_due,
                        }}
                    >
                        <Clock class="size-5" />
                    </div>
                </div>
                <div class="text-foreground mt-2 text-3xl font-bold">
                    {kpis.due_groups.count}
                    <span class="text-muted-foreground text-lg font-medium"
                        >{t('ui.dashboard.kpis.unit_group')}</span
                    >
                </div>
            </div>

            <div class="border-border/60 mt-4 border-t pt-3">
                {#if kpis.due_groups.has_due}
                    <p
                        class="flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400"
                    >
                        <span
                            class="size-2 animate-pulse rounded-full bg-blue-600"
                        ></span>
                        <span>{t('ui.dashboard.kpis.due_groups_ready')}</span>
                    </p>
                {:else}
                    <p
                        class="text-muted-foreground flex items-center gap-1.5 text-sm"
                    >
                        <span class="bg-muted-foreground/40 size-2 rounded-full"
                        ></span>
                        <span>{t('ui.dashboard.kpis.due_groups_empty')}</span>
                    </p>
                {/if}
            </div>
        </button>
    </div>
</section>
