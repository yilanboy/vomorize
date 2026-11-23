<script lang="ts">
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import QuizOptions from '@/components/quiz/QuizOptions.svelte';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type {
        QuizOptionPayload,
        QuizQuestionPayload,
        VocabularyPayload,
    } from '@/types/quiz';

    let {
        question,
        vocabulary,
        selectedOptionId = null,
        onSelect,
        disabled = false,
    }: {
        question: QuizQuestionPayload;
        vocabulary: VocabularyPayload;
        selectedOptionId?: number | null;
        onSelect: (option: QuizOptionPayload) => void;
        disabled?: boolean;
    } = $props();

    const isAnswered = $derived(selectedOptionId !== null);
    const isPlaying = $derived(
        audioPlayer.isPlaying(`quiz-word-${vocabulary.id}`),
    );

    function playAudio(): void {
        if (vocabulary.word_audio_url) {
            void audioPlayer.play(
                vocabulary.word_audio_url,
                `quiz-word-${vocabulary.id}`,
            );
        }
    }
</script>

<div
    class="border-border/80 bg-card mx-auto flex h-[560px] w-full max-w-xl flex-col justify-between rounded-2xl border p-6 shadow-2xs sm:h-[580px] sm:p-8"
>
    <!-- Card Header / Stem: Fixed height across all quiz cards -->
    <div class="flex h-[170px] shrink-0 flex-col sm:h-[180px]">
        <!-- Top Badges -->
        <div class="flex shrink-0 items-center justify-start">
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200/80 bg-blue-50/80 px-3 py-0.5 text-sm font-semibold tracking-wide text-blue-700 dark:border-blue-800/60 dark:bg-blue-950/50 dark:text-blue-300"
            >
                <span class="size-2 animate-pulse rounded-full bg-blue-600"
                ></span>
                <span>{t('ui.quiz.question_types.word_to_definition')}</span>
            </span>
        </div>

        <!-- Word, Pronunciation, & Audio Trigger -->
        <div class="flex flex-1 flex-col justify-start pt-5 sm:pt-6">
            <div class="flex min-h-11 items-center justify-between gap-4">
                <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1.5">
                    <h2
                        class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
                    >
                        {vocabulary.word}
                    </h2>
                    {#if vocabulary.pronunciation}
                        <span
                            class="text-muted-foreground font-mono text-sm sm:text-base"
                        >
                            {vocabulary.pronunciation}
                        </span>
                    {/if}
                    {#if vocabulary.part_of_speech}
                        <span
                            class={{
                                'border-border/80 bg-muted/70 text-foreground/75 inline-flex shrink-0 items-center self-center rounded-md border px-2 py-0.5 text-sm leading-tight font-semibold italic transition-opacity duration-150': true,
                                'visible opacity-100': isAnswered,
                                'invisible opacity-0 select-none': !isAnswered,
                            }}
                            aria-hidden={!isAnswered}
                        >
                            {vocabulary.part_of_speech}
                        </span>
                    {/if}
                </div>

                <button
                    type="button"
                    class={{
                        'flex size-11 shrink-0 items-center justify-center rounded-xl border transition-all duration-150 active:scale-95': true,
                        'border-blue-600 bg-blue-600 text-white shadow-xs ring-4 ring-blue-500/20':
                            isPlaying,
                        'border-border/70 bg-muted/40 text-muted-foreground dark:bg-muted/30 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:bg-blue-950/60 dark:hover:text-blue-300':
                            !isPlaying,
                    }}
                    onclick={playAudio}
                    aria-label={t('ui.intro.card.play_word')}
                    aria-pressed={isPlaying}
                    title={t('ui.intro.card.play_word')}
                >
                    <Volume2
                        class={{
                            'size-5': true,
                            'animate-pulse': isPlaying,
                        }}
                    />
                </button>
            </div>
        </div>
    </div>

    <!-- Options List -->
    <div class="mt-auto">
        <QuizOptions
            options={question.options}
            correctOptionId={question.correct_option_id}
            quizType={question.quiz_type}
            {selectedOptionId}
            {onSelect}
            {disabled}
        />
    </div>
</div>
