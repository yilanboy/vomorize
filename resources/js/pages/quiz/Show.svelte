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
    import CustomQuizResultSummary from '@/components/quiz/CustomQuizResultSummary.svelte';
    import DefinitionToWordCard from '@/components/quiz/DefinitionToWordCard.svelte';
    import WordToDefinitionCard from '@/components/quiz/WordToDefinitionCard.svelte';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import { createNavigationGuard } from '@/lib/navigationGuard';
    import { disableListeningInSteps } from '@/lib/quizTransformer';
    import type {
        MistakeReviewItem,
        QuizOptionPayload,
        SessionStep,
        Stage1SessionData,
    } from '@/types/quiz';

    let {
        session,
        questionCount,
    }: {
        session: Stage1SessionData;
        questionCount: number;
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
    let overrideSteps = $state<SessionStep[] | null>(null);
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
        overrideSteps = disableListeningInSteps(session.steps, currentIndex);
    }

    const activeSteps = $derived(overrideSteps ?? session?.steps ?? []);
    const totalSteps = $derived(activeSteps.length);
    const currentStep = $derived(activeSteps[currentIndex] ?? null);
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
        if (selectedOptionId !== null || !currentStep?.quiz || isAdvancing) {
            return;
        }

        selectedOptionId = option.id;
        isAdvancing = true;

        const isCorrect = option.id === currentStep.quiz.correct_option_id;
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
        overrideSteps = null;
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
    title="{t('ui.quiz.custom.title')} ({t(
        `ui.quiz.custom.count_options.${questionCount}`,
    )})"
/>

<div
    class="bg-background flex min-h-screen flex-col selection:bg-blue-600 selection:text-white"
>
    <!-- Focused Immersive Top Telemetry Bar -->
    <header class="border-border/80 bg-card/95 sticky top-0 z-40 border-b">
        <div
            class="mx-auto flex h-16 max-w-4xl items-center justify-between gap-3 px-4 sm:px-6"
        >
            <!-- Left: Leave button & Session Context Badge -->
            <div class="flex items-center gap-2.5">
                <button
                    type="button"
                    class="border-border/80 hover:bg-muted text-muted-foreground hover:text-foreground flex size-10 cursor-pointer items-center justify-center rounded-xl border transition-colors outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                    onclick={handleLeaveClick}
                    title={t('ui.quiz.custom.top_bar.leave')}
                >
                    <X class="size-5" />
                </button>

                <span
                    class="border-border/80 bg-muted/40 text-muted-foreground hidden items-center rounded-lg border px-2.5 py-1 text-sm font-medium sm:inline-flex"
                >
                    {quizCompleted
                        ? t('ui.quiz.custom.top_bar.completed_badge')
                        : t('ui.quiz.custom.top_bar.session_badge')}
                </span>
            </div>

            <!-- Center: Precision Progress Indicator -->
            <div class="flex flex-1 flex-col items-center gap-1.5 px-2 sm:px-6">
                <div class="flex items-center gap-2">
                    <span
                        class="text-foreground font-mono text-sm font-bold tabular-nums"
                    >
                        {Math.min(currentIndex + 1, totalSteps)}
                    </span>
                    <span class="text-muted-foreground text-sm">/</span>
                    <span
                        class="text-muted-foreground font-mono text-sm tabular-nums"
                    >
                        {totalSteps}
                    </span>
                </div>

                <div
                    class="bg-muted h-2 w-full max-w-xs overflow-hidden rounded-full sm:max-w-sm"
                >
                    <div
                        class="bg-primary h-full rounded-full transition-all duration-200 ease-out"
                        style:width="{progressPercent}%"
                    ></div>
                </div>
            </div>

            <!-- Right: Disable Listening Toggle -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class={{
                        'border-border/80 flex h-10 items-center justify-center gap-2 rounded-xl border px-3 text-sm font-semibold transition-colors outline-none': true,
                        'hover:bg-muted text-muted-foreground hover:text-foreground cursor-pointer':
                            !listeningDisabled,
                        'text-muted-foreground/50 cursor-not-allowed border-dashed':
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
                    <EarOff class="size-4.5 shrink-0" />
                    <span class="hidden sm:inline">
                        {listeningDisabled
                            ? t('ui.quiz.top_bar.listening_disabled_label')
                            : t('ui.quiz.top_bar.cant_listen')}
                    </span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main
        class="flex flex-1 flex-col items-center justify-center px-4 py-8 sm:px-6"
    >
        {#if quizCompleted}
            <CustomQuizResultSummary
                correctCount={correctAnswersCount}
                totalCount={totalSteps}
                {timeSpentSeconds}
                {mistakes}
                onRetry={handleRetry}
                onSettings={handleSettings}
                onLevels={handleLevels}
            />
        {:else if currentStep}
            {#key currentStep.id}
                {#if currentStep.kind === 'quiz' && currentStep.quiz}
                    {#if currentStep.quiz.quiz_type === 'word_to_definition'}
                        <WordToDefinitionCard
                            question={currentStep.quiz}
                            vocabulary={currentStep.vocabulary}
                            {selectedOptionId}
                            onSelect={handleQuizOptionSelect}
                            disabled={isAdvancing}
                        />
                    {:else if currentStep.quiz.quiz_type === 'definition_to_word'}
                        <DefinitionToWordCard
                            question={currentStep.quiz}
                            vocabulary={currentStep.vocabulary}
                            {selectedOptionId}
                            onSelect={handleQuizOptionSelect}
                            disabled={isAdvancing}
                        />
                    {:else if currentStep.quiz.quiz_type === 'audio_to_definition'}
                        <AudioToDefinitionCard
                            question={currentStep.quiz}
                            vocabulary={currentStep.vocabulary}
                            {selectedOptionId}
                            autoPlay={!listeningDisabled}
                            onCantListen={() => {
                                isDisableListeningDialogOpen = true;
                            }}
                            onSelect={handleQuizOptionSelect}
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
