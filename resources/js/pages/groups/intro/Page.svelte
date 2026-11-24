<script lang="ts">
    import { onMount } from 'svelte';
    import { router, setLayoutProps } from '@inertiajs/svelte';
    import { store as saveProgress } from '@/actions/App/Http/Controllers/GroupProgressController';
    import AppHead from '@/components/AppHead.svelte';
    import LeaveConfirmationDialog from '@/components/LeaveConfirmationDialog.svelte';
    import AudioToDefinitionCard from '@/components/quiz/AudioToDefinitionCard.svelte';
    import DefinitionToWordCard from '@/components/quiz/DefinitionToWordCard.svelte';
    import QuizResultSummary from '@/components/quiz/QuizResultSummary.svelte';
    import VocabularyIntroCard from '@/components/quiz/VocabularyIntroCard.svelte';
    import WordToDefinitionCard from '@/components/quiz/WordToDefinitionCard.svelte';
    import type { QuizLayoutProps } from '@/layouts/QuizLayout.svelte';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import {
        learningProgressDb,
        type GroupProgressSummary,
    } from '@/lib/learningProgressDb';
    import { createNavigationGuard } from '@/lib/navigationGuard';
    import type {
        MistakeReviewItem,
        QuizOptionPayload,
        Stage1SessionData,
    } from '@/types/quiz';

    interface GroupData {
        id: number;
        sequence: number;
        level_id: number;
        level_name: string;
        vocabularies_count: number;
    }

    let {
        group,
        session,
        progress = null,
    }: {
        group: GroupData;
        session: Stage1SessionData;
        progress?: GroupProgressSummary | null;
    } = $props();

    let currentIndex = $state(0);
    let selectedOptionId = $state<number | null>(null);
    let listeningDisabled = $state(false);
    let correctAnswersCount = $state(0);
    let mistakes = $state<MistakeReviewItem[]>([]);
    let quizCompleted = $state(false);
    let isSavingProgress = $state(false);
    let finalScore = $state(0);
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
            router.visit(`/levels/${group.level_id}`);
            return;
        }

        pendingTargetUrl = null;
        isLeaveDialogOpen = true;
    }

    function handleConfirmLeave() {
        const targetUrl = pendingTargetUrl;
        pendingTargetUrl = null;
        navigationGuard.bypass();
        router.visit(targetUrl ?? `/levels/${group.level_id}`, {
            replace: !targetUrl,
        });
    }

    function handleConfirmDisableListening() {
        audioPlayer.stop();
        listeningDisabled = true;
    }

    const totalSteps = $derived(session?.steps?.length ?? 30);
    const currentStep = $derived(session?.steps?.[currentIndex] ?? null);
    const activeQuiz = $derived(
        listeningDisabled && currentStep?.fallback_quiz
            ? currentStep.fallback_quiz
            : (currentStep?.quiz ?? null),
    );
    const isPassed = $derived(finalScore >= 80);
    const hasAnswered = $derived(selectedOptionId !== null);
    const isIntroStep = $derived(currentStep?.kind === 'intro');
    const canAdvance = $derived(isIntroStep || hasAnswered);
    const isLastStep = $derived(currentIndex + 1 >= totalSteps);
    const footerLabel = $derived.by(() => {
        if (isIntroStep) {
            return t('ui.intro.card.continue');
        }
        if (isLastStep) {
            return t('ui.quiz.footer.view_results');
        }
        return t('ui.quiz.footer.next');
    });

    $effect.pre(() => {
        setLayoutProps<QuizLayoutProps>({
            currentStep: currentIndex + 1,
            totalSteps,
            onLeave: handleLeaveClick,
            listening: quizCompleted
                ? null
                : {
                      disabled: listeningDisabled,
                      onDisable: handleConfirmDisableListening,
                  },
            footer:
                !quizCompleted && currentStep
                    ? {
                          canAdvance,
                          label: footerLabel,
                          onAdvance: advanceToNextStep,
                      }
                    : null,
        });
    });

    function advanceToNextStep() {
        audioPlayer.stop();
        selectedOptionId = null;

        if (currentIndex + 1 >= totalSteps) {
            void handleQuizCompletion();
        } else {
            currentIndex += 1;
        }
    }

    function handleQuizOptionSelect(option: QuizOptionPayload) {
        if (selectedOptionId !== null || !currentStep) {
            return;
        }

        selectedOptionId = option.id;

        if (currentStep.kind === 'quiz' && activeQuiz) {
            const isCorrect = option.id === activeQuiz.correct_option_id;
            if (isCorrect) {
                correctAnswersCount += 1;
                if (!listeningDisabled && session.correct_audio_url) {
                    void audioPlayer.play(
                        session.correct_audio_url,
                        'sfx-correct',
                    );
                }
            } else {
                if (!listeningDisabled && session.wrong_audio_url) {
                    void audioPlayer.play(session.wrong_audio_url, 'sfx-wrong');
                }

                // Add target word to mistakes if not already added
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
    }

    async function handleQuizCompletion() {
        const computedScore = Math.min(
            100,
            Math.max(0, Math.round((correctAnswersCount / 20) * 100)),
        );
        finalScore = computedScore;
        isSavingProgress = true;
        quizCompleted = true;

        if (progress !== null) {
            // Authenticated user: save via Inertia router
            router.post(
                saveProgress.url(group.id),
                { score: computedScore },
                {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['progress'],
                    onFinish: () => {
                        isSavingProgress = false;
                    },
                },
            );
        } else {
            // Guest: save to IndexedDB
            try {
                await learningProgressDb.recordQuizResult(
                    group.id,
                    group.level_id,
                    computedScore,
                );
            } finally {
                isSavingProgress = false;
            }
        }
    }

    function handleRetry() {
        audioPlayer.stop();
        listeningDisabled = false;
        currentIndex = 0;
        selectedOptionId = null;
        correctAnswersCount = 0;
        mistakes = [];
        quizCompleted = false;
        isSavingProgress = false;
        finalScore = 0;
        router.reload({ only: ['session', 'progress'] });
    }

    function handleDone() {
        audioPlayer.stop();
        router.visit(`/groups/${group.id}`);
    }

    onMount(() => {
        // Guest check: if stage >= 1 in local IndexedDB, redirect to groups.show
        if (!progress) {
            void learningProgressDb.getGroupProgress(group.id).then((local) => {
                if (local && local.stage >= 1) {
                    navigationGuard.bypass();
                    router.visit(`/groups/${group.id}`, { replace: true });
                }
            });
        }

        // Preload audios
        if (session) {
            const urlsToPreload: string[] = [];
            if (session.correct_audio_url) {
                urlsToPreload.push(session.correct_audio_url);
            }
            if (session.wrong_audio_url) {
                urlsToPreload.push(session.wrong_audio_url);
            }

            for (const step of session.steps) {
                if (step.vocabulary) {
                    if (step.vocabulary.word_audio_url) {
                        urlsToPreload.push(step.vocabulary.word_audio_url);
                    }
                    if (step.vocabulary.sentence_audio_url) {
                        urlsToPreload.push(step.vocabulary.sentence_audio_url);
                    }
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
    title="{t('ui.group_detail.title', {
        level_name: group.level_name,
        sequence: group.sequence,
    })} - {t('ui.intro.card.badge')}"
/>

<div
    class="flex w-full flex-col items-center"
    data-progress-saved={quizCompleted && !isSavingProgress ? 'true' : 'false'}
>
    {#if quizCompleted}
        <QuizResultSummary
            score={finalScore}
            passed={isPassed}
            groupId={group.id}
            levelId={group.level_id}
            {mistakes}
            onRetry={handleRetry}
            onDone={handleDone}
        />
    {:else if currentStep}
        {#key currentStep.kind === 'intro' ? currentStep.id : `${currentStep.id}-${activeQuiz?.quiz_type}`}
            {#if currentStep.kind === 'intro'}
                <VocabularyIntroCard
                    vocabulary={currentStep.vocabulary}
                    autoPlay={!listeningDisabled}
                />
            {:else if currentStep.kind === 'quiz' && activeQuiz}
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
                {/if}
            {/if}
        {/key}
    {/if}
</div>

<!-- Leave Confirmation Dialog -->
<LeaveConfirmationDialog
    bind:open={isLeaveDialogOpen}
    onConfirm={handleConfirmLeave}
/>
