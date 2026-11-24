<script lang="ts">
    import { onMount } from 'svelte';
    import { page, router, setLayoutProps } from '@inertiajs/svelte';
    import { index as levelsIndex } from '@/actions/App/Http/Controllers/LevelController';
    import { destroy as complete } from '@/actions/App/Http/Controllers/CustomQuizSessionController';
    import { index as quizIndex } from '@/actions/App/Http/Controllers/QuizController';
    import { show as play } from '@/actions/App/Http/Controllers/QuizPlayController';
    import AppHead from '@/components/AppHead.svelte';
    import LeaveConfirmationDialog from '@/components/LeaveConfirmationDialog.svelte';
    import AudioToDefinitionCard from '@/components/quiz/AudioToDefinitionCard.svelte';
    import DefinitionToWordCard from '@/components/quiz/DefinitionToWordCard.svelte';
    import QuizResultSummary from '@/components/quiz/QuizResultSummary.svelte';
    import SentenceClozeCard from '@/components/quiz/SentenceClozeCard.svelte';
    import SpellingCard from '@/components/quiz/SpellingCard.svelte';
    import WordToDefinitionCard from '@/components/quiz/WordToDefinitionCard.svelte';
    import type { QuizLayoutProps } from '@/layouts/QuizLayout.svelte';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import { createNavigationGuard } from '@/lib/navigationGuard';
    import type {
        CustomQuizSessionData,
        MistakeReviewItem,
        QuizMode,
        QuizOptionPayload,
        SpellingQuestionPayload,
    } from '@/types/quiz';

    let {
        session,
        questionCount,
        quizMode = 'standard',
    }: {
        session: CustomQuizSessionData;
        questionCount: number;
        quizMode?: QuizMode;
    } = $props();

    let currentIndex = $state(0);
    let selectedOptionId = $state<number | null>(null);
    let spellingAnswered = $state(false);
    let listeningDisabled = $state(false);
    let correctAnswersCount = $state(0);
    let mistakes = $state<MistakeReviewItem[]>([]);
    let quizCompleted = $state(false);
    let startTime = $state(Date.now());
    let timeSpentSeconds = $state(0);
    let isLeaveDialogOpen = $state(false);
    let pendingTargetUrl = $state<string | null>(null);

    const navigationGuard = createNavigationGuard({
        enabled: () => !quizCompleted,
        onBlocked: (targetUrl) => {
            pendingTargetUrl = targetUrl ?? null;
            isLeaveDialogOpen = true;
        },
    });

    function handleLeaveClick() {
        if (!navigationGuard.isActive()) {
            router.visit(quizIndex.url());
            return;
        }

        pendingTargetUrl = null;
        isLeaveDialogOpen = true;
    }

    function handleConfirmLeave() {
        const targetUrl = pendingTargetUrl;
        pendingTargetUrl = null;
        navigationGuard.bypass();
        router.visit(targetUrl ?? quizIndex.url(), {
            replace: !targetUrl,
        });
    }

    function handleConfirmDisableListening() {
        audioPlayer.stop();
        listeningDisabled = true;
    }

    const totalSteps = $derived(session?.steps?.length ?? questionCount);
    const currentStep = $derived(session?.steps?.[currentIndex] ?? null);
    const activeQuiz = $derived(
        listeningDisabled && currentStep?.fallback_quiz
            ? currentStep.fallback_quiz
            : (currentStep?.quiz ?? null),
    );
    const hasAnswered = $derived(selectedOptionId !== null || spellingAnswered);
    const isLastStep = $derived(currentIndex + 1 >= totalSteps);

    $effect.pre(() => {
        setLayoutProps<QuizLayoutProps>({
            currentStep: currentIndex + 1,
            totalSteps,
            leaveTitle: t('ui.quiz.custom.top_bar.leave'),
            onLeave: handleLeaveClick,
            listening:
                quizMode !== 'sentence_cloze' && !quizCompleted
                    ? {
                          disabled: listeningDisabled,
                          onDisable: handleConfirmDisableListening,
                      }
                    : null,
            footer:
                !quizCompleted && currentStep
                    ? {
                          canAdvance: hasAnswered,
                          label: isLastStep
                              ? t('ui.quiz.footer.view_results')
                              : t('ui.quiz.footer.next'),
                          onAdvance: advanceToNextStep,
                      }
                    : null,
        });
    });

    function advanceToNextStep() {
        audioPlayer.stop();

        if (currentIndex + 1 < totalSteps) {
            currentIndex += 1;
            selectedOptionId = null;
            spellingAnswered = false;

            const futureStep = session.steps?.[currentIndex + 6];
            if (futureStep?.vocabulary?.word_audio_url) {
                void audioPlayer.preload([
                    futureStep.vocabulary.word_audio_url,
                ]);
            }
        } else {
            handleQuizCompletion();
        }
    }

    function handleQuizOptionSelect(option: QuizOptionPayload) {
        if (
            selectedOptionId !== null ||
            !activeQuiz ||
            !('correct_option_id' in activeQuiz)
        ) {
            return;
        }

        selectedOptionId = option.id;

        const isCorrect = option.id === activeQuiz.correct_option_id;
        if (isCorrect) {
            correctAnswersCount += 1;
            if (!listeningDisabled && session.correct_audio_url) {
                void audioPlayer.play(session.correct_audio_url, 'sfx-correct');
            }
        } else {
            if (!listeningDisabled && session.wrong_audio_url) {
                void audioPlayer.play(session.wrong_audio_url, 'sfx-wrong');
            }

            const target = currentStep.vocabulary;
            if (!mistakes.some((m) => m.id === target.id)) {
                mistakes = [
                    ...mistakes,
                    {
                        id: target.id,
                        word: target.word,
                        part_of_speech: target.part_of_speech,
                        pronunciation: target.pronunciation,
                        definition: target.definition,
                        word_audio_url: target.word_audio_url,
                        sentence_audio_url: target.sentence_audio_url,
                        example_sentence: target.example_sentence,
                        example_translation: target.example_translation,
                    },
                ];
            }
        }
    }

    function handleSpellingResult(isCorrect: boolean) {
        if (spellingAnswered) {
            return;
        }

        spellingAnswered = true;

        if (isCorrect) {
            correctAnswersCount += 1;
            if (!listeningDisabled && session.correct_audio_url) {
                void audioPlayer.play(session.correct_audio_url, 'sfx-correct');
            }
        } else {
            if (!listeningDisabled && session.wrong_audio_url) {
                void audioPlayer.play(session.wrong_audio_url, 'sfx-wrong');
            }

            const target = currentStep.vocabulary;
            if (!mistakes.some((m) => m.id === target.id)) {
                mistakes = [
                    ...mistakes,
                    {
                        id: target.id,
                        word: target.word,
                        part_of_speech: target.part_of_speech,
                        pronunciation: target.pronunciation,
                        definition: target.definition,
                        word_audio_url: target.word_audio_url,
                        sentence_audio_url: target.sentence_audio_url,
                        example_sentence: target.example_sentence,
                        example_translation: target.example_translation,
                    },
                ];
            }
        }
    }

    function handleQuizCompletion() {
        timeSpentSeconds = Math.max(
            1,
            Math.round((Date.now() - startTime) / 1000),
        );
        quizCompleted = true;
        navigationGuard.bypass();

        if (page.props.auth?.user) {
            router.post(
                complete.url(),
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['auth'],
                },
            );
        }
    }

    function handleRetry() {
        audioPlayer.stop();
        listeningDisabled = false;
        navigationGuard.bypass();
        router.visit(play.url());
    }

    function handleSettings() {
        audioPlayer.stop();
        navigationGuard.bypass();
        router.visit(quizIndex.url());
    }

    function handleLevels() {
        audioPlayer.stop();
        navigationGuard.bypass();
        router.visit(levelsIndex.url());
    }

    onMount(() => {
        startTime = Date.now();

        if (session) {
            const urlsToPreload: string[] = [];
            if (session.correct_audio_url) {
                urlsToPreload.push(session.correct_audio_url);
            }
            if (session.wrong_audio_url) {
                urlsToPreload.push(session.wrong_audio_url);
            }

            const initialBatch = session.steps.slice(0, 6);
            for (const step of initialBatch) {
                if (step.vocabulary?.word_audio_url) {
                    urlsToPreload.push(step.vocabulary.word_audio_url);
                }
            }

            void audioPlayer.preload(urlsToPreload);
        }

        return () => {
            audioPlayer.stop();
        };
    });
</script>

<AppHead
    title="{quizMode === 'spelling'
        ? t('ui.quiz.custom.top_bar.session_badge_spelling')
        : quizMode === 'sentence_cloze'
          ? t('ui.quiz.custom.top_bar.session_badge_sentence_cloze')
          : t('ui.quiz.custom.top_bar.session_badge')} ({t(
        `ui.quiz.custom.count_options.${questionCount}`,
    )})"
/>

{#if quizCompleted}
    <QuizResultSummary
        mode="custom"
        correctCount={correctAnswersCount}
        totalCount={totalSteps}
        {timeSpentSeconds}
        {mistakes}
        onRetry={handleRetry}
        onSettings={handleSettings}
        onLevels={handleLevels}
    />
{:else if currentStep}
    {#key `${currentStep.id}-${activeQuiz?.quiz_type}`}
        {#if currentStep.kind === 'quiz' && activeQuiz}
            {#if activeQuiz.quiz_type === 'word_to_definition'}
                <WordToDefinitionCard
                    question={activeQuiz}
                    vocabulary={currentStep.vocabulary}
                    {selectedOptionId}
                    onSelect={handleQuizOptionSelect}
                    disabled={hasAnswered}
                />
            {:else if activeQuiz.quiz_type === 'definition_to_word'}
                <DefinitionToWordCard
                    question={activeQuiz}
                    vocabulary={currentStep.vocabulary}
                    {selectedOptionId}
                    onSelect={handleQuizOptionSelect}
                    disabled={hasAnswered}
                />
            {:else if activeQuiz.quiz_type === 'audio_to_definition'}
                <AudioToDefinitionCard
                    question={activeQuiz}
                    vocabulary={currentStep.vocabulary}
                    {selectedOptionId}
                    autoPlay={!listeningDisabled}
                    onSelect={handleQuizOptionSelect}
                    disabled={hasAnswered}
                />
            {:else if activeQuiz.quiz_type === 'sentence_cloze'}
                <SentenceClozeCard
                    question={activeQuiz}
                    vocabulary={currentStep.vocabulary}
                    {selectedOptionId}
                    onSelect={handleQuizOptionSelect}
                    disabled={hasAnswered}
                />
            {:else if activeQuiz.quiz_type === 'spelling'}
                <SpellingCard
                    question={activeQuiz as SpellingQuestionPayload}
                    vocabulary={currentStep.vocabulary}
                    autoPlay={!listeningDisabled}
                    onResult={handleSpellingResult}
                    disabled={hasAnswered}
                />
            {/if}
        {/if}
    {/key}
{/if}

<!-- Leave Confirmation Dialog -->
<LeaveConfirmationDialog
    bind:open={isLeaveDialogOpen}
    title={t('ui.quiz.custom.top_bar.leave_dialog.title')}
    description={t('ui.quiz.custom.top_bar.leave_dialog.description')}
    cancelText={t('ui.quiz.custom.top_bar.leave_dialog.cancel')}
    confirmText={t('ui.quiz.custom.top_bar.leave_dialog.confirm')}
    onConfirm={handleConfirmLeave}
/>
