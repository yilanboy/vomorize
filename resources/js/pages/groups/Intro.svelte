<script lang="ts">
    import { onMount } from 'svelte';
    import { router, setLayoutProps } from '@inertiajs/svelte';
    import AlertTriangle from '@lucide/svelte/icons/alert-triangle';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import VolumeX from '@lucide/svelte/icons/volume-x';
    import X from '@lucide/svelte/icons/x';
    import { saveProgress } from '@/actions/App/Http/Controllers/GroupController';
    import AppHead from '@/components/AppHead.svelte';
    import AudioToDefinitionCard from '@/components/quiz/AudioToDefinitionCard.svelte';
    import DefinitionToWordCard from '@/components/quiz/DefinitionToWordCard.svelte';
    import QuizResultSummary from '@/components/quiz/QuizResultSummary.svelte';
    import VocabularyIntroCard from '@/components/quiz/VocabularyIntroCard.svelte';
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
    import {
        learningProgressDb,
        type GroupProgressSummary,
    } from '@/lib/learningProgressDb';
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
    let finalScore = $state(0);

    let isLeaveDialogOpen = $state(false);
    let pendingLeaveAction = $state<(() => void) | null>(null);
    let bypassNavigationGuard = false;

    function isGuardActive(): boolean {
        return !quizCompleted && !bypassNavigationGuard;
    }

    const totalSteps = $derived(session?.steps?.length ?? 30);
    const currentStep = $derived(session?.steps?.[currentIndex] ?? null);
    const progressPercent = $derived(
        totalSteps > 0
            ? Math.min(100, Math.round((currentIndex / totalSteps) * 100))
            : 0,
    );
    const isPassed = $derived(finalScore >= 80);

    function toggleAutoPlay() {
        autoPlay = !autoPlay;
        if (typeof window !== 'undefined') {
            localStorage.setItem('vomorize_auto_play', String(autoPlay));
        }
    }

    function advanceToNextStep() {
        selectedOptionId = null;
        isAdvancing = false;

        if (currentIndex + 1 >= totalSteps) {
            void handleQuizCompletion();
        } else {
            currentIndex += 1;
        }
    }

    function handleIntroContinue() {
        advanceToNextStep();
    }

    function handleQuizOptionSelect(option: QuizOptionPayload) {
        if (isAdvancing || selectedOptionId !== null || !currentStep) {
            return;
        }

        selectedOptionId = option.id;
        isAdvancing = true;

        if (currentStep.kind === 'quiz' && currentStep.quiz) {
            const isCorrect = option.id === currentStep.quiz.correct_option_id;
            if (isCorrect) {
                correctAnswersCount += 1;
                if (session.correct_audio_url) {
                    void audioPlayer.play(
                        session.correct_audio_url,
                        'sfx-correct',
                    );
                }
            } else {
                if (session.wrong_audio_url) {
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

        // Auto advance after 1000ms
        setTimeout(() => {
            advanceToNextStep();
        }, 1000);
    }

    async function handleQuizCompletion() {
        const computedScore = Math.min(
            100,
            Math.max(0, Math.round((correctAnswersCount / 20) * 100)),
        );
        finalScore = computedScore;
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
                },
            );
        } else {
            // Guest: save to IndexedDB
            await learningProgressDb.recordQuizResult(
                group.id,
                group.level_id,
                computedScore,
            );
        }
    }

    function requestLeave(action?: () => void) {
        if (!isGuardActive()) {
            if (action) {
                action();
            } else {
                router.visit(`/levels/${group.level_id}`);
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
            router.visit(`/levels/${group.level_id}`, { replace: true });
        }
    }

    function cancelLeave() {
        isLeaveDialogOpen = false;
        pendingLeaveAction = null;
    }

    function handleRetry() {
        audioPlayer.stop();
        currentIndex = 0;
        selectedOptionId = null;
        isAdvancing = false;
        correctAnswersCount = 0;
        mistakes = [];
        quizCompleted = false;
        finalScore = 0;
        router.reload({ only: ['session', 'progress'] });
    }

    function handleDone() {
        audioPlayer.stop();
        router.visit(`/groups/${group.id}`);
    }

    onMount(() => {
        // Load autoplay setting from localStorage
        const stored = localStorage.getItem('vomorize_auto_play');
        if (stored !== null) {
            autoPlay = stored !== 'false';
        }

        // Guest check: if stage >= 1 in local IndexedDB, redirect to groups.show
        if (!progress) {
            void learningProgressDb.getGroupProgress(group.id).then((local) => {
                if (local && local.stage >= 1) {
                    bypassNavigationGuard = true;
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

        // 1. Browser beforeunload guard (reload / close tab)
        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            if (isGuardActive()) {
                e.preventDefault();
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        // 2. Browser back button / mobile swipe back guard (intercept in capture phase before Inertia)
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
                    router.visit(`/levels/${group.level_id}`, {
                        replace: true,
                    });
                });
            }
        };

        window.addEventListener('popstate', handlePopState, { capture: true });

        // 3. Inertia in-app navigation guard
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
    title="{t('ui.group_detail.title', {
        level_id: group.level_id,
        sequence: group.sequence,
    })} - {t('ui.intro.card.badge')}"
/>

<div class="bg-background flex min-h-screen flex-col">
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
                class="hover:bg-muted text-muted-foreground hover:text-foreground flex h-10 w-10 items-center justify-center rounded-xl transition-colors"
                onclick={() => requestLeave()}
                title={t('ui.intro.top_bar.leave')}
            >
                <X class="h-5 w-5" />
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
                        style="width: {progressPercent}%"
                    ></div>
                </div>
            </div>

            <!-- Right: Auto-play toggle button -->
            <button
                type="button"
                class="hover:bg-muted flex h-10 w-10 items-center justify-center rounded-xl transition-colors {autoPlay
                    ? 'text-primary'
                    : 'text-muted-foreground'}"
                onclick={toggleAutoPlay}
                title={autoPlay
                    ? t('ui.intro.top_bar.auto_play_on')
                    : t('ui.intro.top_bar.auto_play_off')}
            >
                {#if autoPlay}
                    <Volume2 class="h-5 w-5" />
                {:else}
                    <VolumeX class="h-5 w-5" />
                {/if}
            </button>
        </div>
    </header>

    <!-- Main Content Area -->
    <main
        class="flex flex-1 flex-col items-center justify-center px-4 py-8 sm:px-6"
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
            {#key currentStep.id}
                {#if currentStep.kind === 'intro'}
                    <VocabularyIntroCard
                        vocabulary={currentStep.vocabulary}
                        {autoPlay}
                        onContinue={handleIntroContinue}
                    />
                {:else if currentStep.kind === 'quiz' && currentStep.quiz}
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
                    <AlertTriangle class="h-6 w-6" />
                </div>
                <DialogTitle class="text-lg font-bold">
                    {t('ui.intro.top_bar.leave_dialog_title')}
                </DialogTitle>
                <DialogDescription
                    class="text-muted-foreground mt-2 text-sm leading-relaxed"
                >
                    {t('ui.intro.top_bar.leave_dialog_desc')}
                </DialogDescription>
            </div>

            <DialogFooter class="flex flex-col gap-2 pt-4 sm:flex-row">
                <button
                    type="button"
                    class="hover:bg-muted text-foreground border-border/80 flex min-h-11 flex-1 items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                    onclick={cancelLeave}
                >
                    {t('ui.intro.top_bar.leave_dialog_cancel')}
                </button>
                <button
                    type="button"
                    class="flex min-h-11 flex-1 items-center justify-center rounded-xl bg-red-600 text-sm font-semibold text-white transition-colors hover:bg-red-700"
                    onclick={confirmLeave}
                >
                    {t('ui.intro.top_bar.leave_dialog_confirm')}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</div>
