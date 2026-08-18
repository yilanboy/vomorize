<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import AnswerFeedbackBar from '@/components/AnswerFeedbackBar.svelte';
    import GroupQuizQuestion from '@/components/GroupQuizQuestion.svelte';
    import QuizLeaveGuard, { disarmLeaveGuard } from '@/components/QuizLeaveGuard.svelte';
    import StickyActionBar from '@/components/StickyActionBar.svelte';
    import {
        calculateNextState,
        getGuestGroupProgress,
        saveGuestGroupProgress,
    } from '@/lib/progress';
    import { answerFor, buildReviewQuestions } from '@/lib/groupQuiz';
    import type { QuizQuestion, VocabularyItem } from '@/lib/groupQuiz';
    import { currentLocale, translations } from '@/lib/locale.svelte';
    import progressRoute from '@/routes/groups/progress';
    import { introduce as introduceRoute, result as resultRoute } from '@/routes/groups';
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
    let currentIndex = $state(0);
    let selectedId = $state<number | null>(null);
    let answered = $state(false);
    let correctAnswers = $state(0);
    let currentQuestion = $derived(questions[currentIndex]);
    let isCorrect = $derived(answered && selectedId === currentQuestion?.vocabulary.id);

    function answer(vocabularyId: number, correct: boolean): void {
        selectedId = vocabularyId;
        answered = true;

        if (correct) {
            correctAnswers += 1;
        }
    }

    function finish(): void {
        const score = Math.round((correctAnswers / questions.length) * 100);
        disarmLeaveGuard();

        if (isGuest) {
            const guestProgress = getGuestGroupProgress(group.id);
            const nextState = calculateNextState(
                progress?.stage ?? guestProgress?.stage ?? 1,
                score,
            );
            saveGuestGroupProgress(group.id, {
                group_id: group.id,
                stage: nextState.stage,
                last_score: nextState.last_score,
                last_reviewed_at: nextState.last_reviewed_at,
                next_review_at: nextState.next_review_at,
            });
            router.visit(
                resultRoute.url(
                    { locale: currentLocale(), group: group.id },
                    { query: { phase: 'quiz', score } },
                ),
            );
        } else {
            router.post(
                progressRoute.store.url({ locale: currentLocale(), group: group.id }),
                { phase: 'quiz', score },
                {
                    preserveScroll: true,
                    onSuccess: () =>
                        router.visit(
                            resultRoute.url(
                                { locale: currentLocale(), group: group.id },
                                { query: { phase: 'quiz', score } },
                            ),
                        ),
                },
            );
        }
    }

    function nextQuestion(): void {
        if (currentIndex < questions.length - 1) {
            currentIndex += 1;
            selectedId = null;
            answered = false;
        } else {
            finish();
        }
    }

    onMount(() => {
        // The server gates members out of the review phase until they clear the introduction,
        // but a guest's stage lives only in their browser, so theirs has to be checked here.
        if (isGuest && (getGuestGroupProgress(group.id)?.stage ?? 0) === 0) {
            // A correction the app makes on the learner's behalf, not their decision.
            disarmLeaveGuard();
            router.visit(
                introduceRoute.url({ locale: currentLocale(), group: group.id }),
            );

            return;
        }

        questions = buildReviewQuestions(vocabularies);
    });
</script>

<QuizLeaveGuard exitUrl={levelRoute.url({ locale: currentLocale(), level: group.level_id })} />

{#if currentQuestion}
    <div class="flex flex-1 flex-col">
        <main class="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-6">
            <div class="mb-4 flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-400">
                <span>{t['start_review']}</span>
                <span>{currentIndex + 1} / {questions.length} · {correctAnswers} ✓</span>
            </div>
            <GroupQuizQuestion
                question={currentQuestion}
                {answered}
                {selectedId}
                onAnswer={answer}
            />
        </main>

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
                class="w-full rounded-xl bg-zinc-900 py-3 text-sm font-semibold text-white dark:bg-zinc-50 dark:text-zinc-950 disabled:opacity-50"
            >
                {t['continue']}
            </button>
        </StickyActionBar>
    </div>
{/if}
