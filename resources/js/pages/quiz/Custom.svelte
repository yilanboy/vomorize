<script lang="ts">
    import { Link, useHttp } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import AnswerFeedbackBar from '@/components/AnswerFeedbackBar.svelte';
    import GroupQuizQuestion from '@/components/GroupQuizQuestion.svelte';
    import QuizLeaveGuard, { disarmLeaveGuard } from '@/components/QuizLeaveGuard.svelte';
    import StickyActionBar from '@/components/StickyActionBar.svelte';
    import { answerFor, buildPracticeQuestions } from '@/lib/groupQuiz';
    import type { QuizQuestion, QuizVocabulary } from '@/lib/groupQuiz';
    import { currentLocaleRouteKey, interpolate, translations } from '@/lib/locale.svelte';
    import { getGuestProgressMap } from '@/lib/progress';
    import { custom as customQuizRoute } from '@/routes/quiz';
    import { count as countRoute, fetch as sampleRoute } from '@/routes/quiz/custom';

    /**
     * Words to test, plus words that exist only to be wrong answers. The two arrive separately
     * because a session drawn from thousands of words would otherwise fill every option slot from
     * its own answers.
     */
    interface SampleResponse {
        targets: QuizVocabulary[];
        distractors: QuizVocabulary[];
    }

    /** Session lengths on offer. A rung above what the learner has learned is never rendered. */
    const PRESET_COUNTS = [10, 30, 50, 100, 200, 300, 400, 500];

    /** Selected on arrival so practice can be started in one tap. */
    const DEFAULT_COUNT = 30;

    let { learned_word_count = null }: { learned_word_count: number | null } = $props();

    let t = $derived(translations());

    let phase = $state<'SELECT' | 'QUIZ' | 'SUMMARY'>('SELECT');
    let questions = $state<QuizQuestion[]>([]);
    let currentIndex = $state(0);
    let selectedId = $state<number | null>(null);
    let answered = $state(false);
    let correctAnswers = $state(0);

    let currentQuestion = $derived(questions[currentIndex]);
    let isCorrect = $derived(answered && selectedId === currentQuestion?.vocabulary.id);
    let score = $derived(
        questions.length > 0 ? Math.round((correctAnswers / questions.length) * 100) : 0,
    );

    /**
     * The groups this session may draw from, as far as the client is concerned.
     *
     * Empty for a signed-in learner, whose pool the server derives from their stored progress and
     * whose requests it reads nothing from. A guest has no such record on the server, so theirs
     * travels with every request.
     */
    let declaredGroupIds = $state<number[]>([]);

    /**
     * A guest's pool size, once the round trip that resolves it has landed. Never consulted for a
     * signed-in learner, whose count arrives with the page.
     */
    let resolvedGuestPoolSize = $state<number | null>(null);

    /**
     * How many words the learner has to practise, or null while that is still unknown.
     *
     * A guest's progress lives in their browser, so theirs is unknown until the round trip lands —
     * and showing a placeholder number would risk offering a session that cannot be delivered.
     */
    let poolSizeIfKnown = $derived(learned_word_count ?? resolvedGuestPoolSize);
    let poolSize = $derived(poolSizeIfKnown ?? 0);
    let isResolvingPoolSize = $derived(poolSizeIfKnown === null);

    /** Undefined until the pool size resolves and the default can be settled. */
    let pickedCount = $state<number | undefined>(undefined);

    let availablePresets = $derived(PRESET_COUNTS.filter((preset) => preset <= poolSize));
    let defaultCount = $derived(
        availablePresets.includes(DEFAULT_COUNT)
            ? DEFAULT_COUNT
            : (availablePresets[availablePresets.length - 1] ?? null),
    );
    let chosenCount = $derived(pickedCount === undefined ? defaultCount : pickedCount);

    /** How many questions the chosen length produces. */
    let questionCount = $derived(chosenCount ?? 0);

    /**
     * Set when a request fails for any reason other than validation.
     *
     * The hook resolves cleanly on a 422 but rethrows everything else, so without this a dropped
     * connection or a server error would leave the learner looking at a page that simply stopped
     * responding — and a guest stuck on the skeleton with no way forward.
     */
    let loadFailed = $state(false);

    /** Thousands-separated, so a four-figure question count reads at a glance. */
    function formatCount(value: number): string {
        return value.toLocaleString('en-US');
    }

    /**
     * Plain JSON calls rather than page visits: a practice set replaces this page's own state
     * instead of navigating. The hook carries CSRF, the in-flight flag, and error handling, so none
     * of that is hand-rolled here. Two instances, because each tracks its own request state.
     */
    const sample = useHttp<{ count: number | null; group_ids: number[] }, SampleResponse>({
        count: null,
        group_ids: [],
    });
    const poolCount = useHttp<{ group_ids: number[] }, { learned_word_count: number }>({
        group_ids: [],
    });

    /**
     * The groups a guest has finished at least one session in — the same rule the server applies to
     * a signed-in learner's stored progress, including a first attempt that was failed.
     */
    function guestLearnedGroupIds(): number[] {
        return Object.entries(getGuestProgressMap())
            .filter(([, progress]) => progress.stage >= 1 || progress.last_reviewed_at)
            .map(([groupId]) => Number(groupId));
    }

    function resolveGuestPoolSize(): void {
        loadFailed = false;
        poolCount.group_ids = declaredGroupIds;

        poolCount
            .post(countRoute.url({ locale: currentLocaleRouteKey() }), {
                onSuccess: (response) => {
                    resolvedGuestPoolSize = response.learned_word_count;
                },
                onHttpException: () => {
                    loadFailed = true;
                },
                onNetworkError: () => {
                    loadFailed = true;
                },
            })
            // The handlers above have already surfaced the failure, so absorbing the rejection here
            // keeps it from becoming an unhandled one.
            .catch(() => {});
    }

    onMount(() => {
        // A signed-in learner's count arrived with the page; there is nothing to resolve.
        if (learned_word_count !== null) {
            return;
        }

        declaredGroupIds = guestLearnedGroupIds();

        if (declaredGroupIds.length === 0) {
            resolvedGuestPoolSize = 0;

            return;
        }

        resolveGuestPoolSize();
    });

    function start(): void {
        if (chosenCount === null) {
            return;
        }

        loadFailed = false;
        sample.count = chosenCount;
        sample.group_ids = declaredGroupIds;

        sample
            .post(sampleRoute.url({ locale: currentLocaleRouteKey() }), {
                onSuccess: ({ targets, distractors }) => {
                    questions = buildPracticeQuestions(targets, distractors);
                    currentIndex = 0;
                    correctAnswers = 0;
                    selectedId = null;
                    answered = false;
                    phase = 'QUIZ';
                },
                onHttpException: () => {
                    loadFailed = true;
                },
                onNetworkError: () => {
                    loadFailed = true;
                },
            })
            .catch(() => {});
    }

    function answer(vocabularyId: number, correct: boolean): void {
        selectedId = vocabularyId;
        answered = true;

        if (correct) {
            correctAnswers += 1;
        }
    }

    function nextQuestion(): void {
        if (currentIndex < questions.length - 1) {
            currentIndex += 1;
            selectedId = null;
            answered = false;

            return;
        }

        // Once the session is over there is nothing left to protect.
        disarmLeaveGuard();
        phase = 'SUMMARY';
    }
</script>

{#if phase === 'QUIZ'}
    <QuizLeaveGuard exitUrl={customQuizRoute.url({ locale: currentLocaleRouteKey() })} />
{/if}

<div class="flex flex-1 flex-col">
    <main class="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-6">
        {#if phase === 'SELECT'}
            <div class="mb-6">
                <h1
                    class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-50"
                >
                    {t['custom_quiz']}
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {t['custom_quiz_subtitle']}
                </p>
            </div>

            {#if isResolvingPoolSize && loadFailed}
                <!--
                    The pool size never arrived, so there is no picker to show. Without a way back
                    from here the learner would be left watching a skeleton that never resolves.
                -->
                <div
                    class="rounded-2xl border border-zinc-200 bg-zinc-50 p-8 text-center shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        {t['load_failed']}
                    </p>
                    <button
                        type="button"
                        onclick={resolveGuestPoolSize}
                        disabled={poolCount.processing}
                        class="mt-4 inline-flex items-center rounded-xl bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800 disabled:opacity-50 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-200"
                    >
                        {poolCount.processing ? t['loading'] : t['try_again']}
                    </button>
                </div>
            {:else if isResolvingPoolSize}
                <div
                    class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div
                        class="mx-auto h-5 w-40 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800"
                    ></div>
                </div>
            {:else if poolSize === 0}
                <div
                    class="rounded-2xl border border-zinc-200 bg-zinc-50 p-8 text-center shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        {t['no_learned_vocab']}
                    </p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {t['no_learned_vocab_desc']}
                    </p>
                    <Link
                        href={`/${currentLocaleRouteKey()}`}
                        class="mt-4 inline-flex items-center rounded-xl bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-200"
                    >
                        {t['go_learn']}
                    </Link>
                </div>
            {:else}
                <div
                    class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <p class="text-center text-sm font-medium text-zinc-900 dark:text-zinc-50">
                        {interpolate(t['learned_word_count'], { count: formatCount(poolSize) })}
                    </p>

                    <p class="mt-6 mb-3 text-sm font-bold text-zinc-900 dark:text-zinc-50">
                        {t['question_count']}
                    </p>

                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                        {#each availablePresets as preset (preset)}
                            <button
                                type="button"
                                onclick={() => (pickedCount = preset)}
                                class={[
                                    'rounded-lg border p-2 text-center text-sm font-semibold transition',
                                    {
                                        'border-zinc-900 bg-zinc-900/10 text-zinc-900 dark:border-zinc-50 dark:bg-zinc-50/10 dark:text-zinc-50':
                                            chosenCount === preset,
                                        'border-zinc-200 bg-zinc-100 text-zinc-900 hover:border-zinc-400 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50 dark:hover:border-zinc-600':
                                            chosenCount !== preset,
                                    },
                                ]}
                            >
                                {preset}
                            </button>
                        {/each}
                    </div>
                </div>
            {/if}
        {:else if phase === 'QUIZ' && currentQuestion}
            <div
                class="mb-4 flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-400"
            >
                <span>{t['custom_quiz']}</span>
                <span>{currentIndex + 1} / {questions.length} · {correctAnswers} ✓</span>
            </div>

            <GroupQuizQuestion
                question={currentQuestion}
                {answered}
                {selectedId}
                onAnswer={answer}
            />
        {:else if phase === 'SUMMARY'}
            <div
                class="space-y-6 rounded-2xl border border-zinc-200 bg-zinc-50 p-8 text-center shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50"
                >
                    <svg
                        class="h-8 w-8"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">
                        {t['practice_quiz_finished']}
                    </h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {t['score']}：<strong class="text-2xl text-zinc-900 dark:text-zinc-50"
                            >{score}%</strong
                        >
                        ({correctAnswers}/{questions.length})
                    </p>
                </div>
            </div>
        {/if}
    </main>

    {#if phase === 'SELECT' && !isResolvingPoolSize && chosenCount !== null}
        <StickyActionBar>
            {#if loadFailed}
                <p class="mb-2 text-center text-sm font-medium text-rose-600 dark:text-rose-400">
                    {t['load_failed']}
                </p>
            {/if}

            <button
                type="button"
                onclick={start}
                disabled={sample.processing}
                class="w-full rounded-xl bg-zinc-900 py-3 text-sm font-semibold text-white shadow-xs hover:bg-zinc-800 disabled:opacity-50 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-200"
            >
                {sample.processing
                    ? t['loading']
                    : t['start_quiz_count']}
            </button>
        </StickyActionBar>
    {:else if phase === 'QUIZ' && currentQuestion}
        <div class="mx-auto w-full max-w-2xl px-4 sm:px-6">
            <AnswerFeedbackBar
                {answered}
                correct={isCorrect}
                correctAnswer={answerFor(currentQuestion.vocabulary, currentQuestion.type)}
            />
        </div>

        <StickyActionBar>
            <button
                type="button"
                onclick={nextQuestion}
                disabled={!answered}
                class="w-full rounded-xl bg-zinc-900 py-3 text-sm font-semibold text-white shadow-xs hover:bg-zinc-800 disabled:opacity-50 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-200"
            >
                {t['continue']}
            </button>
        </StickyActionBar>
    {:else if phase === 'SUMMARY'}
        <StickyActionBar>
            <div class="space-y-2">
                <!-- Same length, freshly drawn words: the request goes back out rather than
                     replaying the set just answered. -->
                <button
                    type="button"
                    onclick={start}
                    disabled={sample.processing}
                    class="w-full rounded-xl bg-zinc-900 py-3 text-sm font-semibold text-white shadow-xs hover:bg-zinc-800 disabled:opacity-50 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-200"
                >
                    {sample.processing
                        ? t['loading']
                        : interpolate(t['retry_quiz_count'], { count: formatCount(questionCount) })}
                </button>

                <button
                    type="button"
                    onclick={() => (phase = 'SELECT')}
                    class="w-full rounded-xl border border-zinc-200 py-3 text-sm font-semibold text-zinc-900 hover:bg-zinc-100 dark:border-zinc-800 dark:text-zinc-50 dark:hover:bg-zinc-900"
                >
                    {t['reselect']}
                </button>
            </div>
        </StickyActionBar>
    {/if}
</div>
