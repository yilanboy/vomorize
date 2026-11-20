<script lang="ts">
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type { VocabularyPayload } from '@/types/quiz';

    let {
        vocabulary,
        index,
        audioKeyPrefix = '',
        headingTag = 'h2',
    }: {
        vocabulary: VocabularyPayload;
        index?: number;
        audioKeyPrefix?: string;
        headingTag?: 'h2' | 'h3' | 'h4';
    } = $props();

    const wordAudioId = $derived(`${audioKeyPrefix}word-${vocabulary.id}`);
    const sentenceAudioId = $derived(
        `${audioKeyPrefix}sentence-${vocabulary.id}`,
    );
    const isWordPlaying = $derived(audioPlayer.isPlaying(wordAudioId));
    const isSentencePlaying = $derived(audioPlayer.isPlaying(sentenceAudioId));
</script>

<section
    class="bg-card border-border/70 hover:border-border scroll-my-24 rounded-2xl border p-5 shadow-2xs transition-all duration-200 sm:p-6"
    aria-label={vocabulary.word}
>
    <!-- Card Header: Index, Word, POS, Pronunciation, Word Audio Button -->
    <div class="flex items-start justify-between gap-4">
        <div class="flex flex-wrap items-baseline gap-2.5 sm:gap-3">
            {#if index !== undefined}
                <span
                    class="text-muted-foreground/60 font-mono text-sm font-bold select-none"
                >
                    {String(index + 1).padStart(2, '0')}
                </span>
            {/if}

            <svelte:element
                this={headingTag}
                class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
            >
                {vocabulary.word}
            </svelte:element>

            {#if vocabulary.part_of_speech}
                <span
                    class="bg-muted text-foreground/80 rounded-md px-2.5 py-0.5 text-sm font-semibold"
                >
                    {vocabulary.part_of_speech}
                </span>
            {/if}

            {#if vocabulary.pronunciation}
                <span
                    class="text-muted-foreground font-mono text-sm sm:text-base"
                >
                    {vocabulary.pronunciation}
                </span>
            {/if}
        </div>

        {#if vocabulary.word_audio_url}
            <!-- 40x40px Word Audio Button (strictly aligned to right rail) -->
            <button
                type="button"
                onclick={() => {
                    void audioPlayer.play(
                        vocabulary.word_audio_url,
                        wordAudioId,
                    );
                }}
                class={{
                    'flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full border transition-all duration-150 active:scale-95': true,
                    'animate-pulse border-blue-600 bg-blue-600 text-white shadow-xs ring-4 ring-blue-500/20':
                        isWordPlaying,
                    'border-border/70 bg-muted/40 text-muted-foreground dark:bg-muted/30 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:bg-blue-950/60 dark:hover:text-blue-300':
                        !isWordPlaying,
                }}
                title={t('ui.group_detail.play_word_audio')}
                aria-label={t('ui.group_detail.play_word_audio')}
            >
                <Volume2 class="size-4.5" />
            </button>
        {/if}
    </div>

    <!-- Definition -->
    <p class="text-foreground mt-2.5 text-base font-medium sm:text-lg">
        {vocabulary.definition}
    </p>

    {#if vocabulary.example_sentence}
        <!-- Integrated Example Sentence Section (strictly aligned to right rail) -->
        <div class="border-border/60 mt-4 border-t pt-3.5">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
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

                {#if vocabulary.sentence_audio_url}
                    <!-- 40x40px Sentence Audio Button (strictly aligned with word audio button) -->
                    <button
                        type="button"
                        onclick={() => {
                            void audioPlayer.play(
                                vocabulary.sentence_audio_url,
                                sentenceAudioId,
                            );
                        }}
                        class={{
                            'flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full border transition-all duration-150 active:scale-95': true,
                            'animate-pulse border-blue-600 bg-blue-600 text-white shadow-xs ring-4 ring-blue-500/20':
                                isSentencePlaying,
                            'border-border/70 bg-muted/40 text-muted-foreground dark:bg-muted/30 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:bg-blue-950/60 dark:hover:text-blue-300':
                                !isSentencePlaying,
                        }}
                        title={t('ui.group_detail.play_sentence_audio')}
                        aria-label={t('ui.group_detail.play_sentence_audio')}
                    >
                        <Volume2 class="size-4.5" />
                    </button>
                {/if}
            </div>
        </div>
    {/if}
</section>
