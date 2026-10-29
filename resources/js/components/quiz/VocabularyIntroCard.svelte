<script lang="ts">
    import { onMount } from 'svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type { VocabularyPayload } from '@/types/quiz';

    let {
        vocabulary,
        autoPlay = true,
        onContinue,
    }: {
        vocabulary?: VocabularyPayload;
        autoPlay?: boolean;
        onContinue: () => void;
    } = $props();

    const isWordPlaying = $derived(
        vocabulary ? audioPlayer.isPlaying(`word-${vocabulary.id}`) : false,
    );
    const isSentencePlaying = $derived(
        vocabulary ? audioPlayer.isPlaying(`sentence-${vocabulary.id}`) : false,
    );

    function playWordAudio(): void {
        if (vocabulary?.word_audio_url) {
            void audioPlayer.play(
                vocabulary.word_audio_url,
                `word-${vocabulary.id}`,
            );
        }
    }

    function playSentenceAudio(): void {
        if (vocabulary?.sentence_audio_url) {
            void audioPlayer.play(
                vocabulary.sentence_audio_url,
                `sentence-${vocabulary.id}`,
            );
        }
    }

    onMount(() => {
        if (autoPlay && vocabulary?.word_audio_url) {
            playWordAudio();
        }

        function handleKeyDown(event: KeyboardEvent) {
            const target = event.target as HTMLElement | null;
            if (
                target &&
                (target.tagName === 'INPUT' ||
                    target.tagName === 'TEXTAREA' ||
                    target.isContentEditable)
            ) {
                return;
            }

            if (event.code === 'Space') {
                event.preventDefault();
                onContinue();
            }
        }

        window.addEventListener('keydown', handleKeyDown);
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    });
</script>

{#if vocabulary}
    <div
        class="border-border/80 bg-card mx-auto flex h-[560px] w-full max-w-xl flex-col justify-between rounded-2xl border p-6 shadow-2xs sm:h-[580px] sm:p-8"
    >
        <!-- Header Badge & Word Title -->
        <div class="flex flex-col gap-2.5">
            <div class="flex items-center justify-between">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-blue-200/80 bg-blue-50/80 px-3 py-0.5 text-sm font-semibold tracking-wide text-blue-700 dark:border-blue-800/60 dark:bg-blue-950/50 dark:text-blue-300"
                >
                    <span class="size-2 animate-pulse rounded-full bg-blue-600"
                    ></span>
                    <span>{t('ui.intro.card.badge')}</span>
                </span>

                <span
                    class="bg-muted text-foreground/80 rounded-md px-2.5 py-0.5 text-sm font-semibold"
                >
                    {vocabulary.part_of_speech}
                </span>
            </div>

            <div class="flex flex-wrap items-baseline gap-3">
                <h2
                    class="text-foreground text-3xl font-extrabold tracking-tight sm:text-4xl"
                >
                    {vocabulary.word}
                </h2>
                <span class="text-muted-foreground font-mono text-base">
                    {vocabulary.pronunciation}
                </span>
            </div>
        </div>

        <!-- Middle Content Area (Pronunciation, Definition, Example) -->
        <div class="flex flex-1 flex-col justify-center gap-3 py-2 sm:gap-3.5">
            <!-- Pronunciation Controls -->
            <div class="flex flex-wrap items-center gap-2.5">
                <button
                    type="button"
                    class={{
                        'inline-flex min-h-[40px] items-center gap-2 rounded-xl border px-3.5 py-1.5 text-sm font-medium transition-all duration-150 active:scale-95': true,
                        'animate-pulse border-blue-600 bg-blue-600 text-white shadow-xs ring-4 ring-blue-500/20':
                            isWordPlaying,
                        'border-border/70 bg-muted/40 text-muted-foreground dark:bg-muted/30 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:bg-blue-950/60 dark:hover:text-blue-300':
                            !isWordPlaying,
                    }}
                    onclick={playWordAudio}
                >
                    <Volume2 class="size-4.5" />
                    <span>{t('ui.intro.card.play_word')}</span>
                </button>

                {#if vocabulary.sentence_audio_url}
                    <button
                        type="button"
                        class={{
                            'inline-flex min-h-[40px] items-center gap-2 rounded-xl border px-3.5 py-1.5 text-sm font-medium transition-all duration-150 active:scale-95': true,
                            'animate-pulse border-blue-600 bg-blue-600 text-white shadow-xs ring-4 ring-blue-500/20':
                                isSentencePlaying,
                            'border-border/70 bg-muted/40 text-muted-foreground dark:bg-muted/30 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:bg-blue-950/60 dark:hover:text-blue-300':
                                !isSentencePlaying,
                        }}
                        onclick={playSentenceAudio}
                    >
                        <Volume2 class="size-4.5" />
                        <span>{t('ui.intro.card.play_sentence')}</span>
                    </button>
                {/if}
            </div>

            <!-- Chinese Definition Section -->
            <div
                class="border-border/60 bg-muted/30 flex flex-col gap-1 rounded-xl border p-3.5"
            >
                <span class="text-muted-foreground text-sm font-semibold">
                    {t('ui.intro.card.definition_label')}
                </span>
                <p class="text-foreground text-base font-bold sm:text-lg">
                    {vocabulary.definition}
                </p>
            </div>

            <!-- Example Sentence Section -->
            {#if vocabulary.example_sentence}
                <div
                    class="border-border/60 bg-muted/30 flex max-h-[140px] flex-col gap-1 overflow-y-auto rounded-xl border p-3.5"
                >
                    <span
                        class="text-sm font-semibold text-blue-600 dark:text-blue-400"
                    >
                        {t('ui.intro.card.example_label')}
                    </span>
                    <p
                        class="text-foreground text-sm leading-relaxed font-medium sm:text-base"
                    >
                        {vocabulary.example_sentence}
                    </p>
                    {#if vocabulary.example_translation}
                        <p class="text-muted-foreground text-sm leading-normal">
                            {vocabulary.example_translation}
                        </p>
                    {/if}
                </div>
            {/if}
        </div>

        <!-- Bottom Action Button -->
        <div class="mt-auto flex flex-col gap-2 pt-2">
            <button
                type="button"
                class="flex min-h-[50px] w-full items-center justify-center gap-2 rounded-xl bg-blue-600 text-base font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-[0.98]"
                onclick={onContinue}
            >
                <span>{t('ui.intro.card.continue')}</span>
                <ArrowRight class="size-4.5" />
            </button>

            <p class="text-muted-foreground text-center text-sm">
                {t('ui.intro.card.keyboard_hint')}
            </p>
        </div>
    </div>
{/if}
