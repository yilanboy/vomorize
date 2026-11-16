<script lang="ts">
    import { onMount, untrack } from 'svelte';
    import { Link, page, setLayoutProps, useForm } from '@inertiajs/svelte';
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
    import { loadPreference, savePreference } from '@/lib/preference';
    import type { BreadcrumbItem } from '@/types';
    import type { QuizMode } from '@/types/quiz';

    let {
        passedGroupCount = null,
        unlockedWordCount = null,
        questionCountOptions,
        defaultQuestionCount,
        availableQuizModes,
        defaultQuizMode,
    }: {
        passedGroupCount?: number | null;
        unlockedWordCount?: number | null;
        questionCountOptions: number[];
        defaultQuestionCount: number;
        availableQuizModes: QuizMode[];
        defaultQuizMode: QuizMode;
    } = $props();

    const QUIZ_COUNT_STORAGE_KEY = 'quiz_question_count';
    const QUIZ_MODE_STORAGE_KEY = 'quiz_mode';

    const breadcrumbs: BreadcrumbItem[] = $derived([
        {
            title: t('ui.levels_page.breadcrumb_home'),
            href: '/',
        },
        {
            title: t('ui.quiz.custom.breadcrumb'),
            href: '/quiz',
        },
    ]);

    $effect(() => {
        setLayoutProps({
            breadcrumbs,
        });
    });

    let localPassedGroupCount = $state<number | null>(null);
    let localUnlockedWordCount = $state<number | null>(null);
    const isClientLoaded = $derived(
        unlockedWordCount !== null || localUnlockedWordCount !== null,
    );

    const form = useForm({
        question_count: untrack(() => defaultQuestionCount),
        quiz_mode: untrack(() => defaultQuizMode),
        passed_group_ids: [] as number[],
    });

    const vocabularyPerGroup = $derived(page.props.vocabularyPerGroup);

    const effectiveWordCount = $derived(
        unlockedWordCount ?? localUnlockedWordCount ?? 0,
    );
    const effectiveGroupCount = $derived(
        passedGroupCount ?? localPassedGroupCount ?? 0,
    );
    const isUnlocked = $derived(effectiveWordCount >= vocabularyPerGroup);
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

    function selectQuestionCount(count: number) {
        form.question_count = count;
        savePreference(QUIZ_COUNT_STORAGE_KEY, String(count));
    }

    function selectQuizMode(mode: QuizMode) {
        form.quiz_mode = mode;
        savePreference(QUIZ_MODE_STORAGE_KEY, mode);
    }

    onMount(async () => {
        if (unlockedWordCount === null) {
            const records = await learningProgressDb.getAllProgress();
            const passed = records.filter((r) => r.stage >= 1);
            form.passed_group_ids = passed.map((r) => r.group_id);
            localPassedGroupCount = passed.length;
            localUnlockedWordCount = passed.length * vocabularyPerGroup;
        }

        const availableWordCount =
            unlockedWordCount ?? localUnlockedWordCount ?? 0;

        const storedCount = Number.parseInt(
            loadPreference(
                QUIZ_COUNT_STORAGE_KEY,
                String(defaultQuestionCount),
            ),
            10,
        );

        if (
            questionCountOptions.includes(storedCount) &&
            availableWordCount >= storedCount
        ) {
            form.question_count = storedCount;
        } else {
            form.question_count = defaultQuestionCount;
        }

        const storedMode = loadPreference(
            QUIZ_MODE_STORAGE_KEY,
            defaultQuizMode,
        );

        if (availableQuizModes.includes(storedMode as QuizMode)) {
            form.quiz_mode = storedMode as QuizMode;
        } else {
            form.quiz_mode = defaultQuizMode;
        }
    });
</script>

<AppHead title={t('ui.quiz.custom.title')} />

<main
    class="mx-auto max-w-5xl space-y-6 px-4 py-6 selection:bg-blue-600 selection:text-white sm:space-y-8 sm:px-6 sm:py-8 lg:px-8"
>
    <Breadcrumbs {breadcrumbs} />

    {#if !isClientLoaded}
        <div
            class="flex min-h-[50vh] items-center justify-center py-6 sm:py-12"
            aria-hidden="true"
        >
            <div
                class="border-border/80 bg-card/60 w-full max-w-lg rounded-2xl border p-8 text-center sm:p-10"
            >
                <div
                    class="bg-muted/60 mx-auto size-14 animate-pulse rounded-2xl"
                ></div>
                <div
                    class="bg-muted/60 mx-auto mt-6 h-7 w-48 animate-pulse rounded-md"
                ></div>
                <div
                    class="bg-muted/40 mx-auto mt-3 h-10 w-full max-w-sm animate-pulse rounded-md"
                ></div>
                <div
                    class="bg-muted/60 mx-auto mt-8 h-11 w-44 animate-pulse rounded-lg"
                ></div>
            </div>
        </div>
    {:else if !isUnlocked}
        <!-- Centered Empty State Card -->
        <section
            class="flex min-h-[50vh] items-center justify-center py-6 sm:py-12"
            aria-labelledby="custom-quiz-empty-title"
        >
            <div
                class="border-border/80 bg-card w-full max-w-lg rounded-2xl border p-8 text-center shadow-xs sm:p-10"
            >
                <div
                    class="bg-muted/70 text-foreground mx-auto flex size-14 items-center justify-center rounded-2xl"
                >
                    <Lock class="size-6" />
                </div>

                <h1
                    id="custom-quiz-empty-title"
                    class="text-foreground mt-6 text-xl font-bold tracking-tight sm:text-2xl"
                >
                    {t('ui.quiz.custom.empty_state.title')}
                </h1>

                <p
                    class="text-muted-foreground mt-3 text-sm leading-relaxed sm:text-base"
                >
                    {t('ui.quiz.custom.empty_state.description')}
                </p>

                <div class="mt-8 flex flex-col items-center gap-3.5">
                    <Button
                        asChild
                        size="lg"
                        class="h-11 w-full gap-2 px-6 text-sm font-semibold sm:w-auto"
                    >
                        {#snippet children(props)}
                            <Link href={levelsIndex.url()} class={props.class}>
                                <span>
                                    {t('ui.quiz.custom.empty_state.action')}
                                </span>
                                <ArrowRight class="size-4" />
                            </Link>
                        {/snippet}
                    </Button>

                    <p class="text-muted-foreground text-sm">
                        {t('ui.quiz.custom.empty_state.guest_hint')}
                    </p>
                </div>
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
                    {#each questionCountOptions as count, idx (count)}
                        {@const isAvailable = effectiveWordCount >= count}
                        {@const isSelected =
                            form.question_count === count && isAvailable}
                        {@const diff = count - effectiveWordCount}

                        <button
                            data-test={`count-option-${count}`}
                            type="button"
                            aria-pressed={isSelected}
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
                                    selectQuestionCount(count);
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
                        data-test="mode-option-standard"
                        type="button"
                        aria-pressed={form.quiz_mode === 'standard'}
                        class={{
                            'flex w-full cursor-pointer flex-col justify-between p-5 text-left transition-colors outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-inset max-lg:rounded-t-[11px] lg:rounded-l-[11px]': true,
                            'bg-blue-50/80 ring-2 ring-blue-600 ring-inset dark:bg-blue-950/35 dark:ring-blue-500':
                                form.quiz_mode === 'standard',
                            'bg-card hover:bg-muted/40':
                                form.quiz_mode !== 'standard',
                        }}
                        onclick={() => selectQuizMode('standard')}
                    >
                        <div class="space-y-2.5">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class={{
                                            'flex size-4.5 shrink-0 items-center justify-center rounded-full border-2': true,
                                            'border-blue-600 dark:border-blue-400':
                                                form.quiz_mode === 'standard',
                                            'border-muted-foreground/40':
                                                form.quiz_mode !== 'standard',
                                        }}
                                    >
                                        {#if form.quiz_mode === 'standard'}
                                            <div
                                                class="size-2.5 rounded-full bg-blue-600 dark:bg-blue-400"
                                            ></div>
                                        {/if}
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

                    <!-- Mode 2: Sentence Cloze (Active) -->
                    <button
                        data-test="mode-option-sentence-cloze"
                        type="button"
                        aria-pressed={form.quiz_mode === 'sentence_cloze'}
                        class={{
                            'flex w-full cursor-pointer flex-col justify-between p-5 text-left transition-colors outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-inset': true,
                            'bg-blue-50/80 ring-2 ring-blue-600 ring-inset dark:bg-blue-950/35 dark:ring-blue-500':
                                form.quiz_mode === 'sentence_cloze',
                            'bg-card hover:bg-muted/40':
                                form.quiz_mode !== 'sentence_cloze',
                        }}
                        onclick={() => selectQuizMode('sentence_cloze')}
                    >
                        <div class="space-y-2.5">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class={{
                                            'flex size-4.5 shrink-0 items-center justify-center rounded-full border-2': true,
                                            'border-blue-600 dark:border-blue-400':
                                                form.quiz_mode ===
                                                'sentence_cloze',
                                            'border-muted-foreground/40':
                                                form.quiz_mode !==
                                                'sentence_cloze',
                                        }}
                                    >
                                        {#if form.quiz_mode === 'sentence_cloze'}
                                            <div
                                                class="size-2.5 rounded-full bg-blue-600 dark:bg-blue-400"
                                            ></div>
                                        {/if}
                                    </div>
                                    <span
                                        class="text-foreground text-base font-bold"
                                    >
                                        {t(
                                            'ui.quiz.custom.modes.sentence_cloze',
                                        )}
                                    </span>
                                </div>

                                <Badge
                                    variant="secondary"
                                    class="shrink-0 text-sm"
                                >
                                    {t(
                                        'ui.quiz.custom.modes.sentence_cloze_tag',
                                    )}
                                </Badge>
                            </div>

                            <p
                                class="text-muted-foreground text-sm leading-relaxed"
                            >
                                {t('ui.quiz.custom.modes.sentence_cloze_desc')}
                            </p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-1.5 pt-2">
                            <span
                                class="border-border/80 bg-background text-foreground rounded-md border px-2 py-0.5 text-sm font-medium"
                            >
                                {t('ui.quiz.custom.modes.sentence_cloze_badge')}
                            </span>
                        </div>
                    </button>

                    <!-- Mode 3: Spelling (Active) -->
                    <button
                        data-test="mode-option-spelling"
                        type="button"
                        aria-pressed={form.quiz_mode === 'spelling'}
                        class={{
                            'flex w-full cursor-pointer flex-col justify-between p-5 text-left transition-colors outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-inset max-lg:rounded-b-[11px] lg:rounded-r-[11px]': true,
                            'bg-blue-50/80 ring-2 ring-blue-600 ring-inset dark:bg-blue-950/35 dark:ring-blue-500':
                                form.quiz_mode === 'spelling',
                            'bg-card hover:bg-muted/40':
                                form.quiz_mode !== 'spelling',
                        }}
                        onclick={() => selectQuizMode('spelling')}
                    >
                        <div class="space-y-2.5">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class={{
                                            'flex size-4.5 shrink-0 items-center justify-center rounded-full border-2': true,
                                            'border-blue-600 dark:border-blue-400':
                                                form.quiz_mode === 'spelling',
                                            'border-muted-foreground/40':
                                                form.quiz_mode !== 'spelling',
                                        }}
                                    >
                                        {#if form.quiz_mode === 'spelling'}
                                            <div
                                                class="size-2.5 rounded-full bg-blue-600 dark:bg-blue-400"
                                            ></div>
                                        {/if}
                                    </div>
                                    <span
                                        class="text-foreground text-base font-bold"
                                    >
                                        {t('ui.quiz.custom.modes.spelling')}
                                    </span>
                                </div>

                                <Badge variant="secondary" class="text-sm">
                                    {t('ui.quiz.custom.modes.spelling_tag')}
                                </Badge>
                            </div>

                            <p
                                class="text-muted-foreground text-sm leading-relaxed"
                            >
                                {t('ui.quiz.custom.modes.spelling_desc')}
                            </p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-1.5 pt-2">
                            <span
                                class="border-border/80 bg-background text-foreground rounded-md border px-2 py-0.5 text-sm font-medium"
                            >
                                {t('ui.quiz.spelling.badge')}
                            </span>
                        </div>
                    </button>
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
                            mode: t(`ui.quiz.custom.modes.${form.quiz_mode}`),
                            count: form.question_count,
                        })}
                    </div>
                    <p class="text-muted-foreground text-sm">
                        <span class="hidden sm:inline">
                            {t('ui.quiz.custom.footer_keyboard_hint')}
                        </span>
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
                        {t('ui.quiz.custom.start_button')}
                    </span>
                    <ArrowRight class="size-4.5 shrink-0" />
                </Button>
            </div>
        </section>
    {/if}
</main>
