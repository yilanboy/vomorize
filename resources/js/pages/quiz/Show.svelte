<script lang="ts">
    import { onMount } from 'svelte';
    import { page, router, setLayoutProps } from '@inertiajs/svelte';
    import AlertTriangle from '@lucide/svelte/icons/alert-triangle';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import VolumeX from '@lucide/svelte/icons/volume-x';
    import X from '@lucide/svelte/icons/x';
    import { index as levelsIndex } from '@/actions/App/Http/Controllers/LevelController';
    import {
        complete,
        index as quizIndex,
        play,
    } from '@/actions/App/Http/Controllers/QuizController';
    import AppHead from '@/components/AppHead.svelte';
    import AudioToDefinitionCard from '@/components/quiz/AudioToDefinitionCard.svelte';
    import CustomQuizResultSummary from '@/components/quiz/CustomQuizResultSummary.svelte';
    import DefinitionToWordCard from '@/components/quiz/DefinitionToWordCard.svelte';
    import WordToDefinitionCard from '@/components/quiz/WordToDefinitionCard.svelte';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type {
        MistakeReviewItem,
        QuizOptionPayload,
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
    let autoPlay = $state(true);
    let correctAnswersCount = $state(0);
    let mistakes = $state<MistakeReviewItem[]>([]);
    let quizCompleted = $state(false);
    let startTime = $state(Date.now());
    let timeSpentSeconds = $state(0);

    let isLeaveDialogOpen = $state(false);
    let pendingLeaveAction = $state<(() => void) | null>(null);
    let bypassNavigationGuard = false;

    function isGuardActive(): boolean {
        return !quizCompleted && !bypassNavigationGuard;
    }

    const totalSteps = $derived(session?.steps?.length ?? questionCount);
    const currentStep = $derived(session?.steps?.[currentIndex] ?? null);
    const progressPercent = $derived(
        totalSteps > 0
            ? Math.min(100, Math.round(((currentIndex + 1) / totalSteps) * 100))
            : 0,
    );

    function toggleAutoPlay() {
        autoPlay = !autoPlay;
        if (typeof window !== 'undefined') {
            localStorage.setItem('vomorize_auto_play', String(autoPlay));
        }
    }

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
            if (session.correct_audio_url) {
                void audioPlayer.play(session.correct_audio_url, 'sfx-correct');
            }
        } else {
            if (session.wrong_audio_url) {
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
        bypassNavigationGuard = true;

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

    function requestLeave(action?: () => void) {
        if (!isGuardActive()) {
            if (action) {
                action();
            } else {
                router.visit(quizIndex.url());
            }
            return;
        }

        pendingLeaveAction = action ?? null;
        isLeaveDialogOpen = true;
    }

    function confirmLeave() {
        isLeaveDialogOpen = false;
        bypassNavigationGuard = true;
        if (pendingLeaveAction) {
            pendingLeaveAction();
        } else {
            router.visit(quizIndex.url(), { replace: true });
        }
    }

    function cancelLeave() {
        isLeaveDialogOpen = false;
        pendingLeaveAction = null;
    }

    function handleRetry() {
        audioPlayer.stop();
        bypassNavigationGuard = true;
        router.visit(play.url());
    }

    function handleSettings() {
        audioPlayer.stop();
        bypassNavigationGuard = true;
        router.visit(quizIndex.url());
    }

    function handleLevels() {
        audioPlayer.stop();
        bypassNavigationGuard = true;
        router.visit(levelsIndex.url());
    }

    onMount(() => {
        startTime = Date.now();

        const stored = localStorage.getItem('vomorize_auto_play');
        if (stored !== null) {
            autoPlay = stored !== 'false';
        }

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

        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            if (isGuardActive()) {
                e.preventDefault();
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        window.history.pushState(
            window.history.state,
            '',
            window.location.href,
        );
        const handlePopState = (e: PopStateEvent) => {
            if (isGuardActive()) {
                e.stopImmediatePropagation();
                window.history.pushState(
                    window.history.state,
                    '',
                    window.location.href,
                );
                requestLeave(() => {
                    router.visit('/quiz', { replace: true });
                });
            }
        };

        window.addEventListener('popstate', handlePopState, { capture: true });

        const removeInertiaListener = router.on('before', (event) => {
            if (isGuardActive()) {
                event.preventDefault();
                requestLeave(() => {
                    router.visit(event.detail.visit.url.href);
                });
            }
        });

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            window.removeEventListener('popstate', handlePopState, {
                capture: true,
            });
            removeInertiaListener();
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
                    onclick={() => requestLeave()}
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

            <!-- Right: Auto-play Toggle -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class={{
                        'border-border/80 hover:bg-muted flex h-10 cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 transition-colors outline-none focus-visible:ring-2 focus-visible:ring-blue-500': true,
                        'border-blue-200 bg-blue-50/70 text-blue-600 dark:border-blue-800/60 dark:bg-blue-950/40 dark:text-blue-400':
                            autoPlay,
                        'text-muted-foreground': !autoPlay,
                    }}
                    onclick={toggleAutoPlay}
                    title={autoPlay
                        ? t('ui.quiz.custom.top_bar.auto_play_on')
                        : t('ui.quiz.custom.top_bar.auto_play_off')}
                >
                    {#if autoPlay}
                        <Volume2 class="size-4.5 shrink-0" />
                    {:else}
                        <VolumeX class="size-4.5 shrink-0" />
                    {/if}
                    <span class="hidden text-sm font-semibold sm:inline">
                        {t('ui.quiz.custom.top_bar.auto_play_label')}
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
                            {autoPlay}
                            onSelect={handleQuizOptionSelect}
                            disabled={isAdvancing}
                        />
                    {/if}
                {/if}
            {/key}
        {/if}
    </main>

    <!-- Leave Confirmation Dialog -->
    <Dialog bind:open={isLeaveDialogOpen}>
        <DialogContent class="sm:max-w-md">
            <div class="flex flex-col items-center justify-center text-center">
                <div
                    class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
                >
                    <AlertTriangle class="size-6" />
                </div>
                <DialogTitle class="text-lg font-bold">
                    {t('ui.quiz.custom.top_bar.leave_dialog.title')}
                </DialogTitle>
                <DialogDescription
                    class="text-muted-foreground mt-2 text-sm leading-relaxed"
                >
                    {t('ui.quiz.custom.top_bar.leave_dialog.description')}
                </DialogDescription>
            </div>

            <DialogFooter class="flex flex-col gap-2 pt-4 sm:flex-row">
                <button
                    type="button"
                    class="hover:bg-muted text-foreground border-border/80 flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                    onclick={cancelLeave}
                >
                    {t('ui.quiz.custom.top_bar.leave_dialog.cancel')}
                </button>
                <button
                    type="button"
                    class="flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-sm font-semibold text-white transition-colors hover:bg-red-700"
                    onclick={confirmLeave}
                >
                    {t('ui.quiz.custom.top_bar.leave_dialog.confirm')}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</div>
