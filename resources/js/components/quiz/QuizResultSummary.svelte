<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Award from '@lucide/svelte/icons/award';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import Clock from '@lucide/svelte/icons/clock';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Settings from '@lucide/svelte/icons/settings';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import XCircle from '@lucide/svelte/icons/x-circle';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type { MistakeReviewItem } from '@/types/quiz';

    let {
        mode = 'group',
        score,
        correctCount,
        totalCount,
        timeSpentSeconds,
        passed,
        groupId: _groupId,
        levelId,
        stage = 1,
        isMasteredRetake = false,
        mistakes = [],
        onRetry,
        onDone,
        onSettings,
        onLevels,
    }: {
        mode?: 'group' | 'custom';
        score?: number;
        correctCount?: number;
        totalCount?: number;
        timeSpentSeconds?: number;
        passed?: boolean;
        groupId?: number;
        levelId?: number;
        stage?: number;
        isMasteredRetake?: boolean;
        mistakes?: MistakeReviewItem[];
        onRetry: () => void;
        onDone?: () => void;
        onSettings?: () => void;
        onLevels?: () => void;
    } = $props();

    const isAuthenticated = $derived(Boolean(page.props.auth?.user));
    const passThresholdScore = $derived(stage >= 6 ? 90 : 80);

    const computedScore = $derived.by(() => {
        if (score !== undefined) {
            return score;
        }
        if (
            correctCount !== undefined &&
            totalCount !== undefined &&
            totalCount > 0
        ) {
            return Math.round((correctCount / totalCount) * 100);
        }
        return 0;
    });

    const isPassed = $derived.by(() => {
        if (passed !== undefined) {
            return passed;
        }
        return computedScore >= 80;
    });

    const formattedTime = $derived.by(() => {
        if (timeSpentSeconds === undefined) {
            return null;
        }
        const minutes = Math.floor(timeSpentSeconds / 60);
        const seconds = timeSpentSeconds % 60;
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    });

    function getStageCooldownLabel(currentStage: number): string {
        switch (currentStage) {
            case 1:
                return t('ui.time.hours', { count: 12 });
            case 2:
                return t('ui.time.days', { count: 1 });
            case 3:
                return t('ui.time.days', { count: 2 });
            case 4:
                return t('ui.time.days', { count: 4 });
            case 5:
                return t('ui.time.days', { count: 7 });
            default:
                return '';
        }
    }

    const resultTitle = $derived.by(() => {
        if (mode === 'custom') {
            return t('ui.quiz.custom.result.title');
        }
        if (isMasteredRetake) {
            return t('ui.quiz.result.title_self_test');
        }
        if (!isPassed) {
            return t('ui.quiz.result.title_failed');
        }
        if (stage >= 6) {
            return t('ui.quiz.result.title_mastered');
        }
        if (stage === 1) {
            return t('ui.quiz.result.title_passed');
        }
        return t('ui.quiz.result.title_passed_stage', { stage });
    });

    const thresholdLabel = $derived.by(() => {
        if (mode === 'custom') {
            return t('ui.quiz.custom.result.subtitle');
        }
        if (isMasteredRetake) {
            return t('ui.quiz.result.self_test_subtitle');
        }
        if (stage === 1) {
            return t('ui.quiz.result.pass_threshold');
        }
        return t('ui.quiz.result.pass_threshold_score', {
            score: passThresholdScore,
        });
    });

    const noticeBannerText = $derived.by(() => {
        if (mode === 'custom') {
            if (mistakes.length === 0) {
                return t('ui.quiz.custom.result.all_correct');
            }
            if (isPassed) {
                return t('ui.quiz.custom.result.subtitle');
            }
            return t('ui.quiz.custom.result.mistakes_subtitle');
        }
        if (isMasteredRetake) {
            return t('ui.quiz.result.self_test_notice');
        }
        if (isPassed) {
            if (stage >= 6) {
                return t('ui.quiz.result.mastered_notice');
            }
            if (stage === 1) {
                return t('ui.quiz.result.cooldown_notice');
            }
            return t('ui.quiz.result.cooldown_notice_stage', {
                stage,
                cooldown: getStageCooldownLabel(stage),
                next_stage: stage + 1,
            });
        }
        if (stage === 1) {
            return t('ui.quiz.result.failed_notice');
        }
        return t('ui.quiz.result.failed_notice_stage', {
            stage,
            threshold: passThresholdScore,
        });
    });

    const retryButtonLabel = $derived.by(() => {
        if (mode === 'custom') {
            return t('ui.quiz.custom.result.btn_retry');
        }
        return stage === 1
            ? t('ui.quiz.result.btn_retry')
            : t('ui.quiz.result.btn_retry_stage', { stage });
    });

    const allCorrectMessage = $derived(
        mode === 'custom'
            ? t('ui.quiz.custom.result.all_correct')
            : t('ui.quiz.result.all_correct'),
    );

    const mistakesTitle = $derived(
        mode === 'custom'
            ? t('ui.quiz.custom.result.mistakes_title', {
                  count: mistakes.length,
              })
            : t('ui.quiz.result.mistakes_title', { count: mistakes.length }),
    );

    function playAudio(url: string, id: string) {
        if (url) {
            void audioPlayer.play(url, id);
        }
    }
</script>

<div class="mx-auto flex w-full max-w-xl flex-col gap-6 pb-12">
    <!-- Score & Status Card -->
    <div
        class={{
            'bg-card flex flex-col items-center gap-5 rounded-2xl border p-6 text-center shadow-sm sm:p-8': true,
            'border-emerald-500/30 bg-emerald-500/5 dark:bg-emerald-950/20':
                isPassed,
            'border-amber-500/30 bg-amber-500/5 dark:bg-amber-950/20':
                !isPassed,
        }}
    >
        <div
            class={{
                'flex h-16 w-16 items-center justify-center rounded-2xl': true,
                'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400':
                    isPassed,
                'bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400':
                    !isPassed,
            }}
        >
            {#if isPassed}
                <Award class="h-8 w-8" />
            {:else}
                <XCircle class="h-8 w-8" />
            {/if}
        </div>

        <div class="flex flex-col gap-1.5">
            <h2 class="text-foreground text-2xl font-bold tracking-tight">
                {resultTitle}
            </h2>
            <p class="text-muted-foreground text-sm font-medium">
                {thresholdLabel}
            </p>
        </div>

        <div class="flex items-baseline gap-2">
            <span
                class={{
                    'text-5xl font-extrabold tracking-tight': true,
                    'text-emerald-600 dark:text-emerald-400': isPassed,
                    'text-amber-600 dark:text-amber-400': !isPassed,
                }}
            >
                {computedScore}
            </span>
            <span class="text-muted-foreground text-xl font-semibold">
                / 100
            </span>
        </div>

        {#if mode === 'custom' && (correctCount !== undefined || formattedTime)}
            <div
                class="text-muted-foreground flex flex-wrap items-center justify-center gap-2.5 text-sm font-medium"
            >
                {#if correctCount !== undefined && totalCount !== undefined}
                    <span>
                        {t('ui.quiz.custom.result.score_stat', {
                            correct: correctCount,
                            total: totalCount,
                        })}
                    </span>
                {/if}
                {#if correctCount !== undefined && formattedTime}
                    <span class="text-muted-foreground/40">•</span>
                {/if}
                {#if formattedTime}
                    <span class="inline-flex items-center gap-1.5">
                        <Clock class="size-4 shrink-0" />
                        <span>
                            {t('ui.quiz.custom.result.time_spent')}: {formattedTime}
                        </span>
                    </span>
                {/if}
            </div>
        {/if}

        <!-- Cooldown / Retry / Notice Banner -->
        <div
            class={{
                'rounded-xl border p-4 text-left text-sm leading-relaxed font-medium': true,
                'border-emerald-500/30 bg-emerald-500/10 text-emerald-900 dark:text-emerald-200':
                    isPassed,
                'border-amber-500/30 bg-amber-500/10 text-amber-900 dark:text-amber-200':
                    !isPassed,
            }}
        >
            {noticeBannerText}
        </div>
    </div>

    <!-- Mistakes Review Section -->
    {#if mistakes.length > 0}
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h3 class="text-foreground text-lg font-bold">
                    {mistakesTitle}
                </h3>
            </div>

            <div class="flex flex-col gap-3">
                {#each mistakes as mistake (mistake.id)}
                    {@const isWordAudioPlaying = audioPlayer.isPlaying(
                        `mistake-word-${mistake.id}`,
                    )}
                    {@const isSentenceAudioPlaying = audioPlayer.isPlaying(
                        `mistake-sentence-${mistake.id}`,
                    )}

                    <div
                        class="border-border/70 bg-card flex flex-col gap-3 rounded-xl border p-4 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-baseline gap-2.5">
                                <span class="text-foreground text-lg font-bold">
                                    {mistake.word}
                                </span>
                                {#if mistake.pronunciation}
                                    <span
                                        class="text-muted-foreground font-mono text-sm"
                                    >
                                        {mistake.pronunciation}
                                    </span>
                                {/if}
                                {#if mistake.part_of_speech}
                                    <span
                                        class="bg-secondary text-secondary-foreground inline-flex items-center rounded-md px-2 py-0.5 text-sm font-medium"
                                    >
                                        {mistake.part_of_speech}
                                    </span>
                                {/if}
                            </div>

                            {#if mistake.word_audio_url}
                                <button
                                    type="button"
                                    class="hover:bg-muted text-foreground border-border/70 flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border transition-colors"
                                    onclick={() =>
                                        playAudio(
                                            mistake.word_audio_url,
                                            `mistake-word-${mistake.id}`,
                                        )}
                                    title={t('ui.intro.card.play_word')}
                                >
                                    <Volume2
                                        class={{
                                            'h-4 w-4': true,
                                            'text-primary animate-pulse':
                                                isWordAudioPlaying,
                                            'text-muted-foreground':
                                                !isWordAudioPlaying,
                                        }}
                                    />
                                </button>
                            {/if}
                        </div>

                        <p class="text-foreground text-base font-semibold">
                            {mistake.definition}
                        </p>

                        {#if mistake.example_sentence}
                            <div class="bg-muted/40 rounded-lg p-2.5">
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <p
                                        class="text-foreground text-sm leading-relaxed font-medium"
                                    >
                                        {mistake.example_sentence}
                                    </p>
                                    {#if mistake.sentence_audio_url}
                                        <button
                                            type="button"
                                            class="hover:bg-muted text-foreground border-border/70 mt-0.5 flex size-6 shrink-0 cursor-pointer items-center justify-center rounded border transition-colors"
                                            onclick={() =>
                                                playAudio(
                                                    mistake.sentence_audio_url,
                                                    `mistake-sentence-${mistake.id}`,
                                                )}
                                            title={t(
                                                'ui.quiz.custom.result.listen_sentence',
                                            )}
                                        >
                                            <Volume2
                                                class={{
                                                    'size-3.5': true,
                                                    'text-primary animate-pulse':
                                                        isSentenceAudioPlaying,
                                                    'text-muted-foreground':
                                                        !isSentenceAudioPlaying,
                                                }}
                                            />
                                        </button>
                                    {/if}
                                </div>
                                {#if mistake.example_translation}
                                    <p
                                        class="text-muted-foreground mt-0.5 text-sm"
                                    >
                                        {mistake.example_translation}
                                    </p>
                                {/if}
                            </div>
                        {/if}
                    </div>
                {/each}
            </div>
        </div>
    {:else}
        <div
            class="bg-card border-border/70 flex items-center gap-3 rounded-xl border p-4 shadow-sm"
        >
            <CheckCircle2
                class="h-6 w-6 shrink-0 text-emerald-600 dark:text-emerald-400"
            />
            <p class="text-foreground text-sm font-medium">
                {allCorrectMessage}
            </p>
        </div>
    {/if}

    <!-- Action Buttons -->
    <div class="flex flex-col gap-3 pt-2">
        {#if mode === 'custom'}
            <button
                type="button"
                class="bg-primary hover:bg-primary/90 text-primary-foreground flex min-h-[50px] w-full cursor-pointer items-center justify-center gap-2 rounded-xl text-base font-semibold shadow-sm transition-all active:scale-[0.99]"
                onclick={onRetry}
            >
                <RotateCcw class="size-4.5" />
                <span>{retryButtonLabel}</span>
            </button>

            {#if onSettings}
                <button
                    type="button"
                    class="hover:bg-muted text-foreground border-border/80 flex min-h-[46px] w-full cursor-pointer items-center justify-center gap-2 rounded-xl border text-sm font-semibold transition-colors"
                    onclick={onSettings}
                >
                    <Settings class="size-4" />
                    <span>{t('ui.quiz.custom.result.btn_settings')}</span>
                </button>
            {/if}

            {#if onLevels || onDone}
                <button
                    type="button"
                    class="hover:bg-muted text-foreground border-border/80 flex min-h-[46px] w-full cursor-pointer items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                    onclick={onLevels ?? onDone}
                >
                    {t('ui.quiz.custom.result.btn_levels')}
                </button>
            {/if}
        {:else if isPassed}
            <button
                type="button"
                class="bg-primary hover:bg-primary/90 text-primary-foreground flex min-h-[50px] w-full cursor-pointer items-center justify-center gap-2 rounded-xl text-base font-semibold shadow-sm transition-all active:scale-[0.99]"
                onclick={onDone}
            >
                {t('ui.quiz.result.btn_done')}
            </button>

            {#if isAuthenticated || !levelId}
                <Link
                    href="/dashboard"
                    class="hover:bg-muted text-foreground border-border/80 flex min-h-[46px] w-full items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                >
                    {t('ui.quiz.result.btn_dashboard')}
                </Link>
            {:else}
                <Link
                    href={`/levels/${levelId}`}
                    class="hover:bg-muted text-foreground border-border/80 flex min-h-[46px] w-full items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                >
                    {t('ui.quiz.result.btn_back_level')}
                </Link>
            {/if}
        {:else}
            <button
                type="button"
                class="bg-primary hover:bg-primary/90 text-primary-foreground flex min-h-[50px] w-full cursor-pointer items-center justify-center gap-2 rounded-xl text-base font-semibold shadow-sm transition-all active:scale-[0.99]"
                onclick={onRetry}
            >
                <RotateCcw class="size-4.5" />
                <span>{retryButtonLabel}</span>
            </button>

            <button
                type="button"
                class="hover:bg-muted text-foreground border-border/80 flex min-h-[46px] w-full cursor-pointer items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                onclick={onDone}
            >
                {t('ui.quiz.result.btn_done')}
            </button>
        {/if}
    </div>
</div>
