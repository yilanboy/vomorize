<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import AnswerFeedbackBar from '@/components/AnswerFeedbackBar.svelte';
    import AudioButton from '@/components/AudioButton.svelte';
    import GroupQuizQuestion from '@/components/GroupQuizQuestion.svelte';
    import QuizLeaveGuard, { disarmLeaveGuard } from '@/components/QuizLeaveGuard.svelte';
    import StickyActionBar from '@/components/StickyActionBar.svelte';
    import { currentLocaleRouteKey, translations } from '@/lib/locale.svelte';
    import { calculateNextState, saveGuestGroupProgress } from '@/lib/progress';
    import {
        answerFor,
        buildIntroductionQuestions,
        definitionOf,
        exampleTranslationOf,
    } from '@/lib/groupQuiz';
    import type { QuizQuestion, VocabularyItem } from '@/lib/groupQuiz';
    import progressRoute from '@/routes/groups/progress';
    import { result as resultRoute } from '@/routes/groups';
    import { show as levelRoute } from '@/routes/levels';

    let {
        group,
        vocabularies = [],
        progress = null,
    } = $props<{
        group: { id: number; sequence: number; level_id: number };
        vocabularies: VocabularyItem[];
        progress: { stage: number; last_score: number | null } | null;
    }>();

    let t = $derived(translations());
    let isGuest = $derived(!page.props.auth?.user);
    let questions = $state<QuizQuestion[]>([]);
    let batchStart = $state(0);
    let mode = $state<'cards' | 'questions'>('cards');
    let cardIndex = $state(0);
    let questionIndex = $state(0);
    let selectedId = $state<number | null>(null);
    let answered = $state(false);
    let correctAnswers = $state(0);

    const batch = $derived(vocabularies.slice(batchStart, batchStart + 2));
    const currentVocabulary = $derived(batch[cardIndex]);
    const currentQuestion = $derived(questions[batchStart + questionIndex]);
    const isCorrect = $derived(answered && selectedId === currentQuestion?.vocabulary.id);

    function nextCard(): void {
        if (cardIndex < batch.length - 1) {
            cardIndex += 1;
        } else {
            mode = 'questions';
            questionIndex = 0;
        }
    }

    function answer(vocabularyId: number, correct: boolean): void {
        selectedId = vocabularyId;
        answered = true;

        if (correct) {
            correctAnswers += 1;
        }
    }

    function nextQuestion(): void {
        if (questionIndex < batch.length - 1) {
            questionIndex += 1;
            selectedId = null;
            answered = false;
        } else if (batchStart + 2 < vocabularies.length) {
            batchStart += 2;
            cardIndex = 0;
            mode = 'cards';
            selectedId = null;
            answered = false;
        } else {
            finish();
        }
    }

    function finish(): void {
        const score = Math.round((correctAnswers / questions.length) * 100);
        disarmLeaveGuard();

        if (score < 90) {
            router.visit(
                resultRoute.url(
                    { locale: currentLocaleRouteKey(), group: group.id },
                    { query: { phase: 'introduce', score } },
                ),
            );

            return;
        }

        if (isGuest) {
            const nextState = calculateNextState(progress?.stage ?? 0, score);
            saveGuestGroupProgress(group.id, {
                group_id: group.id,
                stage: nextState.stage,
                last_score: nextState.last_score,
                last_reviewed_at: nextState.last_reviewed_at,
                next_review_at: nextState.next_review_at,
            });
            router.visit(
                resultRoute.url(
                    { locale: currentLocaleRouteKey(), group: group.id },
                    { query: { phase: 'introduce', score } },
                ),
            );

            return;
        }

        router.post(
            progressRoute.store.url({ locale: currentLocaleRouteKey(), group: group.id }),
            { phase: 'introduce', score },
            {
                onSuccess: () =>
                    router.visit(
                        resultRoute.url(
                            { locale: currentLocaleRouteKey(), group: group.id },
                            { query: { phase: 'introduce', score } },
                        ),
                    ),
            },
        );
    }

    onMount(() => {
        questions = buildIntroductionQuestions(vocabularies);
    });
</script>

<QuizLeaveGuard exitUrl={levelRoute.url({ locale: currentLocaleRouteKey(), level: group.level_id })} />

<div class="flex flex-1 flex-col">
    <main class="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-6">
        <div class="mb-4 flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-400">
            <span
                >{t['start_learning']} · {batchStart +
                    (mode === 'cards' ? cardIndex : questionIndex) +
                    1} / {vocabularies.length}</span
            >
            <span>90%</span>
        </div>

        {#if mode === 'cards' && currentVocabulary}
            <div class="space-y-6 rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-zinc-50">
                            {currentVocabulary.word}
                        </h1>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {currentVocabulary.part_of_speech} · {currentVocabulary.pronunciation}
                        </p>
                    </div>
                    <AudioButton
                        url={currentVocabulary.audio_url}
                        label={t['pronunciation']}
                    />
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-100/40 p-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">
                        {definitionOf(currentVocabulary)}
                    </p>
                </div>
                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-800">
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-50">
                        {currentVocabulary.example_sentence}
                    </p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {exampleTranslationOf(currentVocabulary)}
                    </p>
                </div>
            </div>
        {:else if currentQuestion}
            <GroupQuizQuestion
                question={currentQuestion}
                {answered}
                {selectedId}
                onAnswer={answer}
            />
        {/if}
    </main>

    {#if currentQuestion}
        <div class="mx-auto w-full max-w-2xl px-4 sm:px-6">
            <AnswerFeedbackBar
                {answered}
                correct={isCorrect}
                correctAnswer={answerFor(currentQuestion.vocabulary, currentQuestion.type)}
            />
        </div>
    {/if}

    <StickyActionBar>
        {#if mode === 'cards' && currentVocabulary}
            <button
                type="button"
                onclick={nextCard}
                class="w-full rounded-xl bg-zinc-900 py-3 text-sm font-semibold text-white dark:bg-zinc-50 dark:text-zinc-950"
            >
                {cardIndex < batch.length - 1
                    ? t['continue']
                    : t['start_quiz']}
            </button>
        {:else if currentQuestion}
            <button
                type="button"
                onclick={nextQuestion}
                disabled={!answered}
                class="w-full rounded-xl bg-zinc-900 py-3 text-sm font-semibold text-white dark:bg-zinc-50 dark:text-zinc-950 disabled:opacity-50"
            >
                {t['continue']}
            </button>
        {/if}
    </StickyActionBar>
</div>
