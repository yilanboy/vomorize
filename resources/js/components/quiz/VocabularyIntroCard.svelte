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

    function playWordAudio() {
        if (vocabulary?.word_audio_url) {
            void audioPlayer.play(
                vocabulary.word_audio_url,
                `word-${vocabulary.id}`,
            );
        }
    }

    function playSentenceAudio() {
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
        class="border-border/80 bg-card mx-auto flex min-h-[540px] w-full max-w-xl flex-col justify-between rounded-2xl border p-6 shadow-sm sm:h-[580px] sm:min-h-[580px] sm:p-8"
    >
        <!-- Header Badge & Word Title -->
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span
                    class="bg-primary/10 text-primary border-primary/20 inline-flex items-center rounded-full border px-3 py-1 font-mono text-sm font-semibold"
                >
                    {t('ui.intro.card.badge')}
                </span>

                <span
                    class="bg-secondary text-secondary-foreground inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-medium"
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
                        'hover:bg-muted/80 inline-flex min-h-[40px] items-center gap-2 rounded-xl border px-3.5 py-1.5 text-sm font-medium transition-colors': true,
                        'border-primary bg-primary/10 text-primary':
                            isWordPlaying,
                        'border-border/80 bg-background text-foreground':
                            !isWordPlaying,
                    }}
                    onclick={playWordAudio}
                >
                    <Volume2
                        class={{
                            'h-4 w-4': true,
                            'text-primary animate-pulse': isWordPlaying,
                            'text-muted-foreground': !isWordPlaying,
                        }}
                    />
                    <span>{t('ui.intro.card.play_word')}</span>
                </button>

                {#if vocabulary.sentence_audio_url}
                    <button
                        type="button"
                        class={{
                            'hover:bg-muted/80 inline-flex min-h-[40px] items-center gap-2 rounded-xl border px-3.5 py-1.5 text-sm font-medium transition-colors': true,
                            'border-primary bg-primary/10 text-primary':
                                isSentencePlaying,
                            'border-border/80 bg-background text-foreground':
                                !isSentencePlaying,
                        }}
                        onclick={playSentenceAudio}
                    >
                        <Volume2
                            class={{
                                'h-4 w-4': true,
                                'text-primary animate-pulse': isSentencePlaying,
                                'text-muted-foreground': !isSentencePlaying,
                            }}
                        />
                        <span>{t('ui.intro.card.play_sentence')}</span>
                    </button>
                {/if}
            </div>

            <!-- Chinese Definition Section -->
            <div
                class="bg-muted/40 border-border/50 flex flex-col gap-1 rounded-xl border p-3.5"
            >
                <span class="text-muted-foreground text-xs font-medium">
                    {t('ui.intro.card.definition_label')}
                </span>
                <p class="text-foreground text-base font-bold sm:text-lg">
                    {vocabulary.definition}
                </p>
            </div>

            <!-- Example Sentence Section -->
            {#if vocabulary.example_sentence}
                <div
                    class="bg-muted/40 border-border/50 flex max-h-[140px] flex-col gap-1.5 overflow-y-auto rounded-xl border p-3.5"
                >
                    <span class="text-muted-foreground text-xs font-medium">
                        {t('ui.intro.card.example_label')}
                    </span>
                    <p
                        class="text-foreground text-sm leading-relaxed font-medium sm:text-base"
                    >
                        {vocabulary.example_sentence}
                    </p>
                    {#if vocabulary.example_translation}
                        <p
                            class="text-muted-foreground text-xs leading-relaxed sm:text-sm"
                        >
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
                class="bg-primary hover:bg-primary/90 text-primary-foreground flex min-h-[50px] w-full items-center justify-center gap-2 rounded-xl text-base font-semibold shadow-sm transition-all active:scale-[0.99]"
                onclick={onContinue}
            >
                <span>{t('ui.intro.card.continue')}</span>
                <ArrowRight class="h-4 w-4" />
            </button>

            <p class="text-muted-foreground text-center text-sm">
                {t('ui.intro.card.keyboard_hint')}
            </p>
        </div>
    </div>
{/if}
