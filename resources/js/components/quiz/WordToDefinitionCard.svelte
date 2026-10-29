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
    <div class="flex h-[170px] shrink-0 flex-col justify-between sm:h-[180px]">
        <!-- Top Badges -->
        <div class="flex items-center justify-between">
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200/80 bg-blue-50/80 px-3 py-0.5 text-sm font-semibold tracking-wide text-blue-700 dark:border-blue-800/60 dark:bg-blue-950/50 dark:text-blue-300"
            >
                <span class="size-2 animate-pulse rounded-full bg-blue-600"
                ></span>
                <span>{t('ui.quiz.question_types.word_to_definition')}</span>
            </span>

            <span
                class="bg-muted text-foreground/80 rounded-md px-2.5 py-0.5 text-sm font-semibold"
            >
                {vocabulary.part_of_speech}
            </span>
        </div>

        <!-- Word, Pronunciation, & Audio Trigger -->
        <div class="flex flex-1 items-center justify-between gap-4 py-2">
            <div class="flex flex-col gap-1.5">
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
