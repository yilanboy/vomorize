<script lang="ts">
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import Clock from '@lucide/svelte/icons/clock';
    import Percent from '@lucide/svelte/icons/percent';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Settings from '@lucide/svelte/icons/settings';
    import Target from '@lucide/svelte/icons/target';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import { Button } from '@/components/ui/button';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type { MistakeReviewItem } from '@/types/quiz';

    let {
        correctCount,
        totalCount,
        timeSpentSeconds,
        mistakes = [],
        onRetry,
        onSettings,
        onLevels,
    }: {
        correctCount: number;
        totalCount: number;
        timeSpentSeconds: number;
        mistakes?: MistakeReviewItem[];
        onRetry: () => void;
        onSettings: () => void;
        onLevels: () => void;
    } = $props();

    const accuracy = $derived(
        totalCount > 0 ? Math.round((correctCount / totalCount) * 100) : 0,
    );

    const formattedTime = $derived.by(() => {
        const minutes = Math.floor(timeSpentSeconds / 60);
        const seconds = timeSpentSeconds % 60;
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    });

    function playAudio(url: string, id: string) {
        if (url) {
            void audioPlayer.play(url, id);
        }
    }
</script>

<div class="mx-auto w-full max-w-3xl pb-12">
    <!-- Unified Diagnostic Result Ledger (1px Hairline Architecture) -->
    <section
        class="border-border/80 bg-card divide-border/80 divide-y overflow-hidden rounded-2xl border shadow-xs"
    >
        <!-- Panel 1: Diagnostic Header -->
        <div class="p-6 sm:p-8">
            <div class="space-y-1.5">
                <h1
                    class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
                >
                    {t('ui.quiz.custom.result.title')}
                </h1>
                <p class="text-muted-foreground text-sm sm:text-base">
                    {t('ui.quiz.custom.result.subtitle')}
                </p>
            </div>
        </div>

        <!-- Panel 2: 3-Cell Hairline Telemetry Strip -->
        <div class="divide-border/80 bg-muted/25 grid grid-cols-3 divide-x">
            <!-- Accuracy -->
            <div class="p-4 text-center sm:p-5">
                <div
                    class="text-muted-foreground flex items-center justify-center gap-1.5 text-sm font-medium"
                >
                    <Percent class="size-4 shrink-0" />
                    <span>{t('ui.quiz.custom.result.accuracy_label')}</span>
                </div>
                <div
                    class={{
                        'mt-1.5 font-mono text-xl font-extrabold tabular-nums sm:text-2xl': true,
                        'text-emerald-600 dark:text-emerald-400':
                            accuracy >= 80,
                        'text-blue-600 dark:text-blue-400':
                            accuracy >= 60 && accuracy < 80,
                        'text-amber-600 dark:text-amber-400': accuracy < 60,
                    }}
                >
                    {accuracy}%
                </div>
                <div
                    class="bg-muted mx-auto mt-2 h-1.5 w-full max-w-28 overflow-hidden rounded-full"
                >
                    <div
                        class={{
                            'h-full rounded-full transition-all duration-300': true,
                            'bg-emerald-500': accuracy >= 80,
                            'bg-blue-600': accuracy >= 60 && accuracy < 80,
                            'bg-amber-500': accuracy < 60,
                        }}
                        style:width="{Math.min(100, accuracy)}%"
                    ></div>
                </div>
            </div>

            <!-- Score / Correct Count -->
            <div class="p-4 text-center sm:p-5">
                <div
                    class="text-muted-foreground flex items-center justify-center gap-1.5 text-sm font-medium"
                >
                    <Target class="size-4 shrink-0" />
                    <span>{t('ui.quiz.score')}</span>
                </div>
                <div
                    class="text-foreground mt-1.5 font-mono text-xl font-extrabold tabular-nums sm:text-2xl"
                >
                    {correctCount}
                    <span class="text-muted-foreground font-normal">
                        / {totalCount}
                    </span>
                </div>
            </div>

            <!-- Time Spent -->
            <div class="p-4 text-center sm:p-5">
                <div
                    class="text-muted-foreground flex items-center justify-center gap-1.5 text-sm font-medium"
                >
                    <Clock class="size-4 shrink-0" />
                    <span>{t('ui.quiz.custom.result.time_spent')}</span>
                </div>
                <div
                    class="text-foreground mt-1.5 font-mono text-xl font-extrabold tabular-nums sm:text-2xl"
                >
                    {formattedTime}
                </div>
            </div>
        </div>

        <!-- Panel 3: All-Correct Confirmation OR Mistakes Diagnostic Ledger -->
        {#if mistakes.length === 0}
            <div
                class="flex items-center gap-3 bg-emerald-50/70 px-6 py-5 text-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200"
            >
                <CheckCircle2
                    class="size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                />
                <span class="text-sm font-semibold sm:text-base">
                    {t('ui.quiz.custom.result.all_correct')}
                </span>
            </div>
        {:else}
            <div class="divide-border/80 divide-y">
                <!-- Ledger Section Header -->
                <div
                    class="bg-muted/15 flex flex-col justify-between gap-1 px-6 py-4 sm:flex-row sm:items-baseline sm:px-8"
                >
                    <h2 class="text-foreground text-base font-bold sm:text-lg">
                        {t('ui.quiz.custom.result.mistakes_title', {
                            count: mistakes.length,
                        })}
                    </h2>
                    <span class="text-muted-foreground text-sm">
                        {t('ui.quiz.custom.result.mistakes_subtitle')}
                    </span>
                </div>

                <!-- Mistake Rows -->
                {#each mistakes as mistake (mistake.id)}
                    {@const isWordPlaying = audioPlayer.isPlaying(
                        `mistake-word-${mistake.id}`,
                    )}
                    {@const isSentencePlaying = audioPlayer.isPlaying(
                        `mistake-sentence-${mistake.id}`,
                    )}

                    <div class="space-y-3 p-6 sm:px-8">
                        <div
                            class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"
                        >
                            <!-- Word, IPA, POS -->
                            <div class="flex flex-wrap items-baseline gap-2.5">
                                <span
                                    class="text-foreground text-lg font-extrabold tracking-tight sm:text-xl"
                                >
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
                                        class="bg-secondary text-secondary-foreground inline-flex items-center rounded-md px-2 py-0.5 text-sm font-semibold"
                                    >
                                        {mistake.part_of_speech}
                                    </span>
                                {/if}
                            </div>

                            <!-- Dual Audio Triggers (Word & Sentence) -->
                            <div class="flex flex-wrap items-center gap-2">
                                {#if mistake.word_audio_url}
                                    <button
                                        type="button"
                                        class={{
                                            'inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border px-3 text-sm font-semibold transition-colors outline-none focus-visible:ring-2 focus-visible:ring-blue-500': true,
                                            'border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500':
                                                isWordPlaying,
                                            'border-border/80 bg-background hover:bg-muted text-foreground':
                                                !isWordPlaying,
                                        }}
                                        onclick={() =>
                                            playAudio(
                                                mistake.word_audio_url,
                                                `mistake-word-${mistake.id}`,
                                            )}
                                    >
                                        <Volume2
                                            class={{
                                                'size-4 shrink-0': true,
                                                'animate-pulse': isWordPlaying,
                                                'text-muted-foreground':
                                                    !isWordPlaying,
                                            }}
                                        />
                                        <span>
                                            {t('ui.quiz.custom.result.listen')}
                                        </span>
                                    </button>
                                {/if}

                                {#if mistake.sentence_audio_url}
                                    <button
                                        type="button"
                                        class={{
                                            'inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border px-3 text-sm font-semibold transition-colors outline-none focus-visible:ring-2 focus-visible:ring-blue-500': true,
                                            'border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500':
                                                isSentencePlaying,
                                            'border-border/80 bg-background hover:bg-muted text-foreground':
                                                !isSentencePlaying,
                                        }}
                                        onclick={() =>
                                            playAudio(
                                                mistake.sentence_audio_url,
                                                `mistake-sentence-${mistake.id}`,
                                            )}
                                    >
                                        <Volume2
                                            class={{
                                                'size-4 shrink-0': true,
                                                'animate-pulse':
                                                    isSentencePlaying,
                                                'text-muted-foreground':
                                                    !isSentencePlaying,
                                            }}
                                        />
                                        <span>
                                            {t(
                                                'ui.quiz.custom.result.listen_sentence',
                                            )}
                                        </span>
                                    </button>
                                {/if}
                            </div>
                        </div>

                        <!-- Chinese Definition -->
                        <p class="text-foreground text-base font-semibold">
                            {mistake.definition}
                        </p>

                        <!-- Bilingual Example Sentence Block -->
                        {#if mistake.example_sentence}
                            <div
                                class="border-border/70 bg-muted/35 rounded-xl border px-4 py-3"
                            >
                                <p
                                    class="text-foreground text-sm leading-relaxed font-medium"
                                >
                                    {mistake.example_sentence}
                                </p>
                                {#if mistake.example_translation}
                                    <p
                                        class="text-muted-foreground mt-1 text-sm leading-relaxed"
                                    >
                                        {mistake.example_translation}
                                    </p>
                                {/if}
                            </div>
                        {/if}
                    </div>
                {/each}
            </div>
        {/if}

        <!-- Panel 4: Action Bar -->
        <div
            class="bg-muted/30 flex flex-col items-center justify-between gap-3 p-6 sm:flex-row sm:px-8"
        >
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                <Button
                    size="lg"
                    class="h-12 w-full gap-2 text-base font-semibold sm:w-auto sm:px-6"
                    onclick={onRetry}
                >
                    <RotateCcw class="size-4.5" />
                    <span>{t('ui.quiz.custom.result.btn_retry')}</span>
                </Button>

                <Button
                    variant="outline"
                    size="lg"
                    class="h-12 w-full gap-2 text-base font-semibold sm:w-auto sm:px-6"
                    onclick={onSettings}
                >
                    <Settings class="size-4.5" />
                    <span>{t('ui.quiz.custom.result.btn_settings')}</span>
                </Button>
            </div>

            <Button
                variant="ghost"
                size="lg"
                class="text-muted-foreground hover:text-foreground h-12 w-full text-base sm:w-auto"
                onclick={onLevels}
            >
                <span>{t('ui.quiz.custom.result.btn_levels')}</span>
            </Button>
        </div>
    </section>
</div>
