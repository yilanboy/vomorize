<script lang="ts">
    import { onMount } from 'svelte';
    import { page, router, setLayoutProps } from '@inertiajs/svelte';
    import EarOff from '@lucide/svelte/icons/ear-off';
    import X from '@lucide/svelte/icons/x';
    import { index as levelsIndex } from '@/actions/App/Http/Controllers/LevelController';
    import { destroy as complete } from '@/actions/App/Http/Controllers/CustomQuizSessionController';
    import { index as quizIndex } from '@/actions/App/Http/Controllers/QuizController';
    import { show as play } from '@/actions/App/Http/Controllers/QuizPlayController';
    import AppHead from '@/components/AppHead.svelte';
    import DisableListeningDialog from '@/components/DisableListeningDialog.svelte';
    import LeaveConfirmationDialog from '@/components/LeaveConfirmationDialog.svelte';
    import AudioToDefinitionCard from '@/components/quiz/AudioToDefinitionCard.svelte';
    import DefinitionToWordCard from '@/components/quiz/DefinitionToWordCard.svelte';
    import QuizResultSummary from '@/components/quiz/QuizResultSummary.svelte';
    import SpellingCard from '@/components/quiz/SpellingCard.svelte';
    import WordToDefinitionCard from '@/components/quiz/WordToDefinitionCard.svelte';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import { createNavigationGuard } from '@/lib/navigationGuard';
    import type {
        CustomQuizSessionData,
        MistakeReviewItem,
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
        quizMode?: 'standard' | 'spelling';
    } = $props();

    setLayoutProps({
        showHeader: false,
        showFooter: false,
    });

    let currentIndex = $state(0);
    let selectedOptionId = $state<number | null>(null);
    let isAdvancing = $state(false);
    let listeningDisabled = $state(false);
    let isDisableListeningDialogOpen = $state(false);
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
    const progressPercent = $derived(
        totalSteps > 0
            ? Math.min(100, Math.round(((currentIndex + 1) / totalSteps) * 100))
            : 0,
    );

    function advanceToNextStep() {
        if (currentIndex + 1 < totalSteps) {
            currentIndex += 1;
            selectedOptionId = null;
            isAdvancing = false;

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
            !('correct_option_id' in activeQuiz) ||
            isAdvancing
        ) {
            return;
        }

        selectedOptionId = option.id;
        isAdvancing = true;

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

        setTimeout(() => {
            advanceToNextStep();
        }, 1000);
    }

    function handleSpellingResult(isCorrect: boolean) {
        if (isAdvancing) {
            return;
        }

        if (isCorrect) {
            isAdvancing = true;
            correctAnswersCount += 1;
            if (!listeningDisabled && session.correct_audio_url) {
                void audioPlayer.play(session.correct_audio_url, 'sfx-correct');
            }

            setTimeout(() => {
                advanceToNextStep();
            }, 1000);
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

    function handleSpellingNext() {
        advanceToNextStep();
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
        : t('ui.quiz.custom.top_bar.session_badge')} ({t(
        `ui.quiz.custom.count_options.${questionCount}`,
    )})"
/>

<div
    class="bg-background flex min-h-screen flex-col selection:bg-blue-600 selection:text-white"
>
    <!-- Focused Immersive Top Bar -->
    <header
        class="border-border/80 bg-background/80 sticky top-0 z-40 border-b backdrop-blur-md"
    >
        <div
            class="mx-auto flex h-16 max-w-4xl items-center justify-between px-4 sm:px-6"
        >
            <!-- Left: Leave button -->
            <button
                type="button"
                class="hover:bg-muted text-muted-foreground hover:text-foreground flex size-10 items-center justify-center rounded-xl transition-colors"
                onclick={handleLeaveClick}
                title={t('ui.quiz.custom.top_bar.leave')}
            >
                <X class="size-5" />
            </button>

            <!-- Center: Progress indicator -->
            <div class="flex flex-1 flex-col items-center gap-1.5 px-4 sm:px-8">
                <div class="flex items-center gap-2">
                    <span
                        class="text-foreground font-mono text-sm font-semibold"
                    >
                        {Math.min(currentIndex + 1, totalSteps)}
                    </span>
                    <span class="text-muted-foreground text-sm">/</span>
                    <span class="text-muted-foreground font-mono text-sm">
                        {totalSteps}
                    </span>
                </div>

                <div
                    class="bg-muted h-2 w-full max-w-xs overflow-hidden rounded-full sm:max-w-sm"
                >
                    <div
                        class="bg-primary h-full rounded-full transition-all duration-300 ease-out"
                        style:width="{progressPercent}%"
                    ></div>
                </div>
            </div>

            <!-- Right: Disable Listening Toggle -->
            <button
                type="button"
                class={{
                    'flex size-10 items-center justify-center rounded-xl transition-colors': true,
                    'hover:bg-muted text-muted-foreground hover:text-foreground cursor-pointer':
                        !listeningDisabled,
                    'text-muted-foreground/40 cursor-not-allowed':
                        listeningDisabled,
                }}
                disabled={listeningDisabled}
                onclick={() => {
                    isDisableListeningDialogOpen = true;
                }}
                title={listeningDisabled
                    ? t('ui.quiz.top_bar.listening_disabled')
                    : t('ui.quiz.top_bar.cant_listen')}
            >
                <EarOff class="size-5" />
            </button>
        </div>
    </header>

    <!-- Main Content Area -->
    <main
        class="flex flex-1 flex-col items-center justify-center px-4 py-8 sm:px-6"
    >
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
                            disabled={isAdvancing}
                        />
                    {:else if activeQuiz.quiz_type === 'definition_to_word'}
                        <DefinitionToWordCard
                            question={activeQuiz}
                            vocabulary={currentStep.vocabulary}
                            {selectedOptionId}
                            onSelect={handleQuizOptionSelect}
                            disabled={isAdvancing}
                        />
                    {:else if activeQuiz.quiz_type === 'audio_to_definition'}
                        <AudioToDefinitionCard
                            question={activeQuiz}
                            vocabulary={currentStep.vocabulary}
                            {selectedOptionId}
                            autoPlay={!listeningDisabled}
                            onSelect={handleQuizOptionSelect}
                            disabled={isAdvancing}
                        />
                    {:else if activeQuiz.quiz_type === 'spelling'}
                        <SpellingCard
                            question={activeQuiz as SpellingQuestionPayload}
                            vocabulary={currentStep.vocabulary}
                            autoPlay={!listeningDisabled}
                            onResult={handleSpellingResult}
                            onNext={handleSpellingNext}
                            disabled={isAdvancing}
                        />
                    {/if}
                {/if}
            {/key}
        {/if}
    </main>

    <!-- Leave Confirmation Dialog -->
    <LeaveConfirmationDialog
        bind:open={isLeaveDialogOpen}
        title={t('ui.quiz.custom.top_bar.leave_dialog.title')}
        description={t('ui.quiz.custom.top_bar.leave_dialog.description')}
        cancelText={t('ui.quiz.custom.top_bar.leave_dialog.cancel')}
        confirmText={t('ui.quiz.custom.top_bar.leave_dialog.confirm')}
        onConfirm={handleConfirmLeave}
    />

    <!-- Disable Listening Confirmation Dialog -->
    <DisableListeningDialog
        bind:open={isDisableListeningDialogOpen}
        onConfirm={handleConfirmDisableListening}
    />
</div>
