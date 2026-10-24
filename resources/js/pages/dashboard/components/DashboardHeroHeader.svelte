<script lang="ts">
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Play from '@lucide/svelte/icons/play';
    import SlidersHorizontal from '@lucide/svelte/icons/sliders-horizontal';
    import { t } from '@/lib/i18n';

    let {
        isEmptyState = false,
        userName = '',
        dueCount = 0,
        recommendedUrl = '#action-center',
    }: {
        isEmptyState?: boolean;
        userName?: string;
        dueCount?: number;
        recommendedUrl?: string;
    } = $props();

    function scrollToQueue(event: MouseEvent) {
        event.preventDefault();
        const el = document.getElementById('action-center');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>

<div
    class="border-border/80 flex flex-col justify-between gap-4 border-b pb-4 md:flex-row md:items-end"
>
    <div>
        <h1 class="text-foreground text-3xl font-bold tracking-tight">
            {t('ui.dashboard.title')}
        </h1>
        <p class="text-muted-foreground mt-2 text-base leading-relaxed">
            {#if isEmptyState}
                {t('ui.dashboard.welcome_new')}
            {:else}
                {t('ui.dashboard.welcome_active_prefix', { name: userName })}
                <span class="font-semibold text-blue-600 dark:text-blue-400">
                    {t('ui.dashboard.welcome_active_count', { count: dueCount })}
                </span>
                {t('ui.dashboard.welcome_active_suffix')}
            {/if}
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button
            type="button"
            class="border-border bg-card text-foreground hover:bg-muted/70 inline-flex h-11 items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium shadow-2xs transition"
        >
            <SlidersHorizontal class="mr-2 size-4 text-muted-foreground" />
            <span>{t('ui.dashboard.actions.custom_quiz')}</span>
        </button>

        {#if isEmptyState}
            <a
                href={recommendedUrl}
                class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-2xs transition hover:bg-blue-700 hover:shadow-xs dark:bg-blue-600 dark:hover:bg-blue-500"
            >
                <ArrowRight class="size-4" />
                <span>{t('ui.dashboard.actions.start_new')}</span>
            </a>
        {:else}
            <a
                href="#action-center"
                onclick={scrollToQueue}
                class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-2xs transition hover:bg-blue-700 hover:shadow-xs dark:bg-blue-600 dark:hover:bg-blue-500"
            >
                <Play class="size-4 fill-white" />
                <span>
                    {t('ui.dashboard.actions.start_review', { count: dueCount })}
                </span>
            </a>
        {/if}
    </div>
</div>
