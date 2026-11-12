<script lang="ts">
    import { onMount } from 'svelte';
    import { Link, setLayoutProps, useForm } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Check from '@lucide/svelte/icons/check';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import Lock from '@lucide/svelte/icons/lock';
    import Shuffle from '@lucide/svelte/icons/shuffle';
    import { index as levelsIndex } from '@/actions/App/Http/Controllers/LevelController';
    import { store as start } from '@/actions/App/Http/Controllers/CustomQuizSessionController';
    import AppHead from '@/components/AppHead.svelte';
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { t } from '@/lib/i18n';
    import { learningProgressDb } from '@/lib/learningProgressDb';
    import type { BreadcrumbItem } from '@/types';

    let {
        passedGroupCount = null,
        unlockedWordCount = null,
    }: {
        passedGroupCount?: number | null;
        unlockedWordCount?: number | null;
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('ui.levels_page.breadcrumb_home'),
            href: '/',
        },
        {
            title: t('ui.quiz.custom.breadcrumb'),
            href: '/quiz',
        },
    ];

    setLayoutProps({
        breadcrumbs,
    });

    let localPassedGroupCount = $state<number | null>(null);
    let localUnlockedWordCount = $state<number | null>(null);
    let isClientLoaded = $state(false);

    const form = useForm({
        question_count: 10,
        quiz_mode: 'standard' as 'standard' | 'sentence_cloze' | 'spelling',
        passed_group_ids: [] as number[],
    });

    const COUNT_OPTIONS = [10, 20, 30, 50, 100] as const;

    onMount(async () => {
        if (unlockedWordCount === null) {
            const records = await learningProgressDb.getAllProgress();
            const passed = records.filter((r) => r.stage >= 1);
            form.passed_group_ids = passed.map((r) => r.group_id);
            localPassedGroupCount = passed.length;
            localUnlockedWordCount = passed.length * 10;
        }
        isClientLoaded = true;
    });

    const effectiveWordCount = $derived(
        unlockedWordCount ?? localUnlockedWordCount ?? 0,
    );
    const effectiveGroupCount = $derived(
        passedGroupCount ?? localPassedGroupCount ?? 0,
    );
    const isUnlocked = $derived(effectiveWordCount >= 10);
    const poolCoveragePercent = $derived(
        effectiveWordCount > 0
            ? Math.min(
                  100,
                  Math.round((form.question_count / effectiveWordCount) * 100),
              )
            : 0,
    );

    function startQuiz() {
        if (!isUnlocked || form.processing) {
            return;
        }

        form.post(start.url(), {
            preserveScroll: true,
        });
    }

    function handleKeydown(event: KeyboardEvent) {
        if (
            event.target instanceof HTMLInputElement ||
            event.target instanceof HTMLTextAreaElement ||
            event.metaKey ||
            event.ctrlKey ||
            event.altKey
        ) {
            return;
        }

        if (event.key === 'Enter' && isUnlocked && !form.processing) {
            event.preventDefault();
            startQuiz();
        }
    }
</script>

<svelte:window onkeydown={handleKeydown} />

<AppHead title={t('ui.quiz.custom.title')} />

<main
    class="mx-auto max-w-5xl space-y-6 px-4 py-6 selection:bg-blue-600 selection:text-white sm:space-y-8 sm:px-6 sm:py-8 lg:px-8"
>
    <Breadcrumbs {breadcrumbs} />

    {#if isClientLoaded && !isUnlocked}
        <!-- Architectural Empty State: Unlock Blueprint Workbench -->
        <section
            class="border-border/80 bg-card divide-border/80 divide-y overflow-hidden rounded-2xl border shadow-xs"
        >
            <!-- Top Header & Pool Readiness Telemetry -->
            <div class="p-6 sm:p-8">
                <div
                    class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div class="max-w-2xl">
                        <h1
                            class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
                        >
                            {t('ui.quiz.custom.title')}
                        </h1>
                        <p
                            class="text-muted-foreground mt-2 text-sm leading-relaxed sm:text-base"
                        >
                            {t('ui.quiz.custom.subtitle')}
                        </p>
                    </div>

                    <div
                        class="border-border/80 bg-muted/30 flex shrink-0 flex-col gap-2 rounded-xl border px-4 py-3 sm:min-w-64"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <span
                                class="text-muted-foreground text-sm font-medium"
                            >
                                {t('ui.quiz.custom.empty_state.status_badge')}
                            </span>
                            <span
                                class="text-foreground font-mono text-sm font-bold tabular-nums"
                            >
                                0%
                            </span>
                        </div>
                        <div class="grid grid-cols-10 gap-1">
                            {#each Array.from({ length: 10 }, (_, idx) => idx) as segment (segment)}
                                <div
                                    class="bg-border/80 h-1.5 rounded-xs"
                                ></div>
                            {/each}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unlock Requirement Callout -->
            <div class="bg-muted/20 p-6 sm:p-8">
                <div class="max-w-2xl space-y-3">
                    <h2
                        class="text-foreground text-xl font-bold tracking-tight sm:text-2xl"
                    >
                        {t('ui.quiz.custom.empty_state.title')}
                    </h2>
                    <p
                        class="text-muted-foreground text-sm leading-relaxed sm:text-base"
                    >
                        {t('ui.quiz.custom.empty_state.description')}
                    </p>
                    <div
                        class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                    >
                        <CheckCircle2 class="size-4 shrink-0" />
                        <span>{t('ui.quiz.custom.empty_state.milestone')}</span>
                    </div>
                </div>
            </div>

            <!-- 3-Step Unlock Progression Grid -->
            <div
                class="divide-border/80 grid grid-cols-1 divide-y md:grid-cols-3 md:divide-x md:divide-y-0"
            >
                <div class="space-y-2 p-6">
                    <div class="flex items-center justify-between">
                        <span
                            class="text-foreground text-base font-bold tracking-tight"
                        >
                            {t('ui.quiz.custom.empty_state.step_1_title')}
                        </span>
                        <span
                            class="border-border bg-muted/60 text-muted-foreground rounded-md border px-2 py-0.5 font-mono text-sm font-semibold tabular-nums"
                        >
                            {t('ui.quiz.custom.empty_state.step_1_badge')}
                        </span>
                    </div>
                    <p class="text-muted-foreground text-sm leading-relaxed">
                        {t('ui.quiz.custom.empty_state.step_1_desc')}
                    </p>
                </div>

                <div class="space-y-2 p-6">
                    <div class="flex items-center justify-between">
                        <span
                            class="text-foreground text-base font-bold tracking-tight"
                        >
                            {t('ui.quiz.custom.empty_state.step_2_title')}
                        </span>
                        <span
                            class="border-border bg-muted/60 text-muted-foreground rounded-md border px-2 py-0.5 font-mono text-sm font-semibold tabular-nums"
                        >
                            {t('ui.quiz.custom.empty_state.step_2_badge')}
                        </span>
                    </div>
                    <p class="text-muted-foreground text-sm leading-relaxed">
                        {t('ui.quiz.custom.empty_state.step_2_desc')}
                    </p>
                </div>

                <div class="space-y-2 p-6">
                    <div class="flex items-center justify-between">
                        <span
                            class="text-foreground text-base font-bold tracking-tight"
                        >
                            {t('ui.quiz.custom.empty_state.step_3_title')}
                        </span>
                        <span
                            class="rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 font-mono text-sm font-semibold text-blue-700 tabular-nums dark:border-blue-800/60 dark:bg-blue-950/50 dark:text-blue-300"
                        >
                            {t('ui.quiz.custom.empty_state.step_3_badge')}
                        </span>
                    </div>
                    <p class="text-muted-foreground text-sm leading-relaxed">
                        {t('ui.quiz.custom.empty_state.step_3_desc')}
                    </p>
                </div>
            </div>

            <!-- Action Footer -->
            <div
                class="bg-muted/30 flex flex-col items-start justify-between gap-4 p-6 sm:flex-row sm:items-center sm:px-8"
            >
                <p class="text-muted-foreground text-sm">
                    {t('ui.quiz.custom.empty_state.guest_hint')}
                </p>

                <Button
                    asChild
                    size="lg"
                    class="h-12 w-full shrink-0 gap-2 px-6 text-base font-semibold sm:w-auto"
                >
                    {#snippet children(props)}
                        <Link href={levelsIndex.url()} class={props.class}>
                            <span>
                                {t('ui.quiz.custom.empty_state.action')}
                            </span>
                            <ArrowRight class="size-4.5" />
                        </Link>
                    {/snippet}
                </Button>
            </div>
        </section>
    {:else}
        <!-- Unified Command Workbench (1px Hairline Instrument Architecture) -->
        <section
            class="border-border/80 bg-card divide-border/80 divide-y overflow-hidden rounded-2xl border shadow-xs"
        >
            <!-- Panel 0: Header & Live Word Pool Telemetry -->
            <div class="p-6 sm:p-8">
                <div
                    class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="max-w-xl">
                        <h1
                            class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
                        >
                            {t('ui.quiz.custom.title')}
                        </h1>
                        <p
                            class="text-muted-foreground mt-2 text-sm leading-relaxed sm:text-base"
                        >
                            {t('ui.quiz.custom.subtitle')}
                        </p>
                    </div>

                    <!-- Live Pool Readout & Sampling Rule -->
                    <div
                        class="border-border/70 flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:gap-5 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-6"
                    >
                        <div class="space-y-1">
                            <div
                                class="flex items-center gap-2 text-sm font-semibold text-emerald-600 dark:text-emerald-400"
                            >
                                <CheckCircle2 class="size-4 shrink-0" />
                                <span>
                                    {t('ui.quiz.custom.unlocked_stat', {
                                        words: effectiveWordCount,
                                        groups: effectiveGroupCount,
                                    })}
                                </span>
                            </div>

                            <div
                                class="text-muted-foreground flex flex-wrap items-center gap-2 text-sm"
                            >
                                <span
                                    class="inline-flex items-center gap-1.5 font-medium"
                                >
                                    <Shuffle class="text-primary size-3.5" />
                                    <span>{t('ui.quiz.custom.pool_rule')}</span>
                                </span>
                                <span aria-hidden="true">·</span>
                                <span class="font-mono tabular-nums">
                                    {t('ui.quiz.custom.pool_coverage', {
                                        percent: poolCoveragePercent,
                                    })}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 1: Question Count Matrix (5-Stop Segmented Instrument) -->
            <div class="p-6 sm:p-8">
                <div class="flex items-center justify-between">
                    <h2
                        class="text-foreground text-base font-bold tracking-tight sm:text-lg"
                    >
                        {t('ui.quiz.custom.select_count_label')}
                    </h2>
                </div>

                <div
                    class="border-border/80 divide-border/80 mt-4 grid grid-cols-2 divide-x divide-y overflow-hidden rounded-xl border sm:grid-cols-5 sm:divide-y-0"
                >
                    {#each COUNT_OPTIONS as count, idx (count)}
                        {@const isAvailable = effectiveWordCount >= count}
                        {@const isSelected =
                            form.question_count === count && isAvailable}
                        {@const diff = count - effectiveWordCount}

                        <button
                            type="button"
                            class={{
                                'group relative flex flex-col justify-between p-4 text-left transition-colors outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-inset': true,
                                'max-sm:rounded-tl-[11px] sm:rounded-l-[11px]':
                                    idx === 0,
                                'max-sm:rounded-tr-[11px]': idx === 1,
                                'col-span-2 max-sm:rounded-b-[11px] sm:col-span-1 sm:rounded-r-[11px]':
                                    idx === 4,
                                'bg-blue-50/80 ring-2 ring-blue-600 ring-inset dark:bg-blue-950/35 dark:ring-blue-500':
                                    isSelected,
                                'bg-card hover:bg-muted/40 cursor-pointer':
                                    isAvailable && !isSelected,
                                'bg-muted/20 cursor-not-allowed opacity-60':
                                    !isAvailable,
                            }}
                            disabled={!isAvailable}
                            onclick={() => {
                                if (isAvailable) {
                                    form.question_count = count;
                                }
                            }}
                        >
                            <!-- Top Row: Status Icon -->
                            <div
                                class="flex min-h-6 w-full items-center justify-end"
                            >
                                {#if isSelected}
                                    <span
                                        class="flex size-5 items-center justify-center rounded-full bg-blue-600 text-white dark:bg-blue-500"
                                    >
                                        <Check class="size-3 stroke-3" />
                                    </span>
                                {:else if !isAvailable}
                                    <Lock
                                        class="text-muted-foreground size-4 shrink-0"
                                    />
                                {/if}
                            </div>

                            <!-- Middle Row: Question Count -->
                            <div class="mt-4">
                                <div
                                    class={{
                                        'font-mono text-2xl font-extrabold tracking-tight tabular-nums': true,
                                        'text-blue-600 dark:text-blue-400':
                                            isSelected,
                                        'text-foreground':
                                            isAvailable && !isSelected,
                                        'text-muted-foreground': !isAvailable,
                                    }}
                                >
                                    {t(`ui.quiz.custom.count_options.${count}`)}
                                </div>
                            </div>

                            <!-- Bottom Row: Tier Tag or Unlock Threshold -->
                            <div class="mt-4 pt-1">
                                {#if isAvailable}
                                    <span
                                        class={{
                                            'inline-flex items-center rounded-md px-2 py-0.5 text-sm font-semibold': true,
                                            'bg-blue-600/15 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300':
                                                isSelected,
                                            'bg-muted text-muted-foreground':
                                                !isSelected,
                                        }}
                                    >
                                        {t(
                                            `ui.quiz.custom.count_badges.${count}`,
                                        )}
                                    </span>
                                {:else}
                                    <span
                                        class="text-muted-foreground block text-sm leading-snug"
                                    >
                                        {t('ui.quiz.custom.requires_unlock', {
                                            diff,
                                        })}
                                    </span>
                                {/if}
                            </div>
                        </button>
                    {/each}
                </div>
            </div>

            <!-- Panel 2: Quiz Mode Selection (3-Column Hairline Grid) -->
            <div class="p-6 sm:p-8">
                <div
                    class="flex flex-col justify-between gap-1 sm:flex-row sm:items-baseline"
                >
                    <h2
                        class="text-foreground text-base font-bold tracking-tight sm:text-lg"
                    >
                        {t('ui.quiz.custom.select_mode_label')}
                    </h2>
                    <span class="text-muted-foreground text-sm">
                        {t('ui.quiz.custom.select_mode_hint')}
                    </span>
                </div>

                <div
                    class="border-border/80 divide-border/80 mt-4 grid grid-cols-1 divide-y overflow-hidden rounded-xl border lg:grid-cols-3 lg:divide-x lg:divide-y-0"
                >
                    <!-- Mode 1: Standard (Active) -->
                    <button
                        type="button"
                        class={{
                            'flex w-full cursor-pointer flex-col justify-between p-5 text-left transition-colors outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-inset max-lg:rounded-t-[11px] lg:rounded-l-[11px]': true,
                            'bg-blue-50/80 ring-2 ring-blue-600 ring-inset dark:bg-blue-950/35 dark:ring-blue-500':
                                form.quiz_mode === 'standard',
                            'bg-card hover:bg-muted/40':
                                form.quiz_mode !== 'standard',
                        }}
                        onclick={() => (form.quiz_mode = 'standard')}
                    >
                        <div class="space-y-2.5">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex size-4.5 shrink-0 items-center justify-center rounded-full border-2 border-blue-600 dark:border-blue-400"
                                    >
                                        <div
                                            class="size-2.5 rounded-full bg-blue-600 dark:bg-blue-400"
                                        ></div>
                                    </div>
                                    <span
                                        class="text-foreground text-base font-bold"
                                    >
                                        {t('ui.quiz.custom.modes.standard')}
                                    </span>
                                </div>

                                <Badge variant="secondary" class="text-sm">
                                    {t('ui.quiz.custom.modes.standard_tag')}
                                </Badge>
                            </div>

                            <p
                                class="text-muted-foreground text-sm leading-relaxed"
                            >
                                {t('ui.quiz.custom.modes.standard_desc')}
                            </p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-1.5 pt-2">
                            <span
                                class="border-border/80 bg-background text-foreground rounded-md border px-2 py-0.5 text-sm font-medium"
                            >
                                {t('ui.quiz.question_types.word_to_definition')}
                            </span>
                            <span
                                class="border-border/80 bg-background text-foreground rounded-md border px-2 py-0.5 text-sm font-medium"
                            >
                                {t('ui.quiz.question_types.definition_to_word')}
                            </span>
                            <span
                                class="border-border/80 bg-background text-foreground rounded-md border px-2 py-0.5 text-sm font-medium"
                            >
                                {t(
                                    'ui.quiz.question_types.audio_to_definition',
                                )}
                            </span>
                        </div>
                    </button>

                    <!-- Mode 2: Sentence Cloze (Coming Soon) -->
                    <div
                        class="bg-muted/20 flex flex-col justify-between p-5 text-left opacity-65"
                    >
                        <div class="space-y-2.5">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="border-muted-foreground/40 size-4.5 shrink-0 rounded-full border-2"
                                    ></div>
                                    <span
                                        class="text-muted-foreground text-base font-semibold"
                                    >
                                        {t(
                                            'ui.quiz.custom.modes.sentence_cloze',
                                        )}
                                    </span>
                                </div>

                                <Badge
                                    variant="outline"
                                    class="text-muted-foreground shrink-0 text-sm"
                                >
                                    {t('ui.quiz.custom.modes.coming_soon')}
                                </Badge>
                            </div>

                            <p
                                class="text-muted-foreground text-sm leading-relaxed"
                            >
                                {t('ui.quiz.custom.modes.sentence_cloze_desc')}
                            </p>
                        </div>
                    </div>

                    <!-- Mode 3: Spelling (Coming Soon) -->
                    <div
                        class="bg-muted/20 flex flex-col justify-between p-5 text-left opacity-65 max-lg:rounded-b-[11px] lg:rounded-r-[11px]"
                    >
                        <div class="space-y-2.5">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="border-muted-foreground/40 size-4.5 shrink-0 rounded-full border-2"
                                    ></div>
                                    <span
                                        class="text-muted-foreground text-base font-semibold"
                                    >
                                        {t('ui.quiz.custom.modes.spelling')}
                                    </span>
                                </div>

                                <Badge
                                    variant="outline"
                                    class="text-muted-foreground shrink-0 text-sm"
                                >
                                    {t('ui.quiz.custom.modes.coming_soon')}
                                </Badge>
                            </div>

                            <p
                                class="text-muted-foreground text-sm leading-relaxed"
                            >
                                {t('ui.quiz.custom.modes.spelling_desc')}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 3: Command Launch Footer -->
            <div
                class="bg-muted/30 flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center sm:px-8"
            >
                <div class="space-y-1">
                    <div
                        class="text-foreground text-sm font-semibold tabular-nums"
                    >
                        {t('ui.quiz.custom.summary_line', {
                            mode: t('ui.quiz.custom.modes.standard'),
                            count: form.question_count,
                        })}
                    </div>
                    <p class="text-muted-foreground text-sm">
                        {t('ui.quiz.custom.footer_hint')}
                    </p>
                </div>

                <Button
                    size="lg"
                    class="h-13 w-full shrink-0 gap-2.5 px-7 text-base font-semibold whitespace-nowrap shadow-xs sm:w-auto"
                    disabled={!isUnlocked || form.processing}
                    onclick={startQuiz}
                >
                    <span>
                        {t('ui.quiz.custom.start_button', {
                            count: form.question_count,
                        })}
                    </span>
                    <ArrowRight class="size-4.5 shrink-0" />
                    <span
                        class="bg-primary-foreground/20 text-primary-foreground ml-1 hidden rounded px-2 py-0.5 font-mono text-sm sm:inline-flex"
                    >
                        Enter ↵
                    </span>
                </Button>
            </div>
        </section>
    {/if}
</main>
