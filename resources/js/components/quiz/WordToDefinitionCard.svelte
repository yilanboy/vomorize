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

    function playAudio() {
        if (vocabulary.word_audio_url) {
            void audioPlayer.play(
                vocabulary.word_audio_url,
                `quiz-word-${vocabulary.id}`,
            );
        }
    }
</script>

<div
    class="border-border/80 bg-card mx-auto flex min-h-[540px] w-full max-w-xl flex-col justify-between rounded-2xl border p-6 shadow-sm sm:h-[580px] sm:min-h-[580px] sm:p-8"
>
    <!-- Card Header / Stem -->
    <div class="flex h-[160px] flex-col justify-between sm:h-[170px]">
        <div class="flex items-center justify-between">
            <span
                class="bg-primary/10 text-primary border-primary/20 inline-flex items-center rounded-full border px-3 py-1 font-mono text-sm font-semibold"
            >
                {t('ui.quiz.question_types.word_to_definition')}
            </span>

            <span
                class="bg-secondary text-secondary-foreground inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-medium"
            >
                {vocabulary.part_of_speech}
            </span>
        </div>

        <div class="flex flex-1 items-center justify-between gap-4 py-2">
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

            <button
                type="button"
                class={{
                    'hover:bg-muted/80 text-foreground border-border/80 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border transition-colors': true,
                    'border-primary bg-primary/10 text-primary': isPlaying,
                    'bg-background': !isPlaying,
                }}
                onclick={playAudio}
                title={t('ui.intro.card.play_word')}
            >
                <Volume2
                    class={{
                        'h-5 w-5': true,
                        'text-primary animate-pulse': isPlaying,
                        'text-muted-foreground': !isPlaying,
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
