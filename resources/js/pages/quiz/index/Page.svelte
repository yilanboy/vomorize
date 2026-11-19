<script lang="ts">
    import { onMount, untrack } from 'svelte';
    import { Link, page, useForm } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import Lock from '@lucide/svelte/icons/lock';
    import { index as levelsIndex } from '@/actions/App/Http/Controllers/LevelController';
    import { store as start } from '@/actions/App/Http/Controllers/CustomQuizSessionController';
    import AppHead from '@/components/AppHead.svelte';
    import TactileTile from '@/components/TactileTile.svelte';
    import { Button } from '@/components/ui/button';
    import { t } from '@/lib/i18n';
    import { learningProgressDb } from '@/lib/learningProgressDb';
    import { loadPreference, savePreference } from '@/lib/preference';
    import { cn } from '@/lib/utils';
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

    const modeTestIds: Record<QuizMode, string> = {
        standard: 'mode-option-standard',
        sentence_cloze: 'mode-option-sentence-cloze',
        spelling: 'mode-option-spelling',
    };

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
        <!-- Overview Header -->
        <header
            class="border-border/80 flex flex-col justify-between gap-4 border-b pb-4 md:flex-row md:items-end md:pb-6"
        >
            <div class="max-w-2xl">
                <h1
                    class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
                >
                    {t('ui.quiz.custom.title')}
                </h1>
                <p
                    class="text-muted-foreground mt-2 hidden text-sm leading-relaxed sm:text-base md:block"
                >
                    {t('ui.quiz.custom.subtitle')}
                </p>
            </div>

            <div
                class="hidden shrink-0 items-center gap-2 text-sm font-semibold text-emerald-600 md:flex dark:text-emerald-400"
            >
                <CheckCircle2 class="size-4 shrink-0" />
                <span>
                    {t('ui.quiz.custom.unlocked_stat', {
                        words: effectiveWordCount,
                        groups: effectiveGroupCount,
                    })}
                </span>
            </div>
        </header>

        <!-- Question Count Selection -->
        <section class="space-y-4">
            <h2
                class="text-foreground text-base font-bold tracking-tight sm:text-lg"
            >
                {t('ui.quiz.custom.select_count_label')}
            </h2>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                {#each questionCountOptions as count (count)}
                    {@const isAvailable = effectiveWordCount >= count}
                    {@const isSelected =
                        form.question_count === count && isAvailable}
                    {@const diff = count - effectiveWordCount}

                    <TactileTile
                        data-test={`count-option-${count}`}
                        aria-pressed={isSelected}
                        variant={isSelected ? 'primary' : 'muted'}
                        depressed={!isAvailable}
                        disabled={!isAvailable}
                        class={cn('flex-col gap-1 px-4 py-4 text-center', {
                            'opacity-70': !isAvailable,
                        })}
                        onclick={() => {
                            if (isAvailable) {
                                selectQuestionCount(count);
                            }
                        }}
                    >
                        <span
                            class={{
                                'font-mono text-xl font-extrabold tracking-tight tabular-nums sm:text-2xl': true,
                                'text-blue-700 dark:text-blue-300': isSelected,
                                'text-foreground': isAvailable && !isSelected,
                                'text-muted-foreground': !isAvailable,
                            }}
                        >
                            {t(`ui.quiz.custom.count_options.${count}`)}
                        </span>

                        {#if !isAvailable}
                            <span
                                class="text-muted-foreground inline-flex items-center gap-1 text-sm"
                            >
                                <Lock class="size-3.5 shrink-0" />
                                <span>
                                    {t('ui.quiz.custom.requires_unlock', {
                                        diff,
                                    })}
                                </span>
                            </span>
                        {/if}
                    </TactileTile>
                {/each}
            </div>
        </section>

        <!-- Quiz Mode Selection -->
        <section class="space-y-4">
            <h2
                class="text-foreground text-base font-bold tracking-tight sm:text-lg"
            >
                {t('ui.quiz.custom.select_mode_label')}
            </h2>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {#each availableQuizModes as mode (mode)}
                    {@const isSelected = form.quiz_mode === mode}

                    <TactileTile
                        data-test={modeTestIds[mode]}
                        aria-pressed={isSelected}
                        variant={isSelected ? 'primary' : 'muted'}
                        class="flex-col items-start justify-start gap-1.5 p-5 text-left"
                        onclick={() => selectQuizMode(mode)}
                    >
                        <span
                            class={{
                                'text-base font-bold': true,
                                'text-blue-700 dark:text-blue-300': isSelected,
                                'text-foreground': !isSelected,
                            }}
                        >
                            {t(`ui.quiz.custom.modes.${mode}`)}
                        </span>

                        <p
                            class={{
                                'text-sm leading-relaxed': true,
                                'text-blue-700/85 dark:text-blue-300/85':
                                    isSelected,
                                'text-muted-foreground': !isSelected,
                            }}
                        >
                            {t(`ui.quiz.custom.modes.${mode}_desc`)}
                        </p>
                    </TactileTile>
                {/each}
            </div>
        </section>

        <!-- Launch Action -->
        <div class="flex justify-end pt-2">
            <Button
                size="lg"
                class="h-12 w-full gap-2.5 px-7 text-base font-semibold whitespace-nowrap shadow-xs sm:w-auto"
                disabled={!isUnlocked || form.processing}
                onclick={startQuiz}
            >
                <span>
                    {t('ui.quiz.custom.start_button')}
                </span>
                <ArrowRight class="size-4.5 shrink-0" />
            </Button>
        </div>
    {/if}
</main>
