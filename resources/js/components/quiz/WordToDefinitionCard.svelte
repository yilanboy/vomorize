<script lang="ts">
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import QuizOptions from '@/components/quiz/QuizOptions.svelte';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type { QuizOptionPayload, QuizQuestionPayload } from '@/types/quiz';

    let {
        question,
        selectedOptionId = null,
        onSelect,
        disabled = false,
    }: {
        question: QuizQuestionPayload;
        selectedOptionId?: number | null;
        onSelect: (option: QuizOptionPayload) => void;
        disabled?: boolean;
    } = $props();

    const targetWord = $derived(question.target_word);

    function playAudio() {
        if (targetWord.word_audio_url) {
            void audioPlayer.play(
                targetWord.word_audio_url,
                `quiz-word-${targetWord.id}`,
            );
        }
    }
</script>

<div
    class="border-border/80 bg-card mx-auto flex w-full max-w-xl flex-col gap-6 rounded-2xl border p-6 shadow-sm sm:p-8"
>
    <!-- Card Header / Stem -->
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span
                class="bg-primary/10 text-primary border-primary/20 inline-flex items-center rounded-full border px-3 py-1 font-mono text-sm font-semibold"
            >
                {t('ui.quiz.question_types.word_to_definition')}
            </span>

            <span
                class="bg-secondary text-secondary-foreground inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-medium"
            >
                {targetWord.part_of_speech}
            </span>
        </div>

        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-wrap items-baseline gap-3">
                <h2
                    class="text-foreground text-3xl font-extrabold tracking-tight sm:text-4xl"
                >
                    {targetWord.word}
                </h2>
                <span class="text-muted-foreground font-mono text-base">
                    {targetWord.pronunciation}
                </span>
            </div>

            <button
                type="button"
                class="hover:bg-muted/80 text-foreground border-border/80 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border transition-colors {audioPlayer.isPlaying(
                    `quiz-word-${targetWord.id}`,
                )
                    ? 'border-primary bg-primary/10 text-primary'
                    : 'bg-background'}"
                onclick={playAudio}
                title={t('ui.intro.card.play_word')}
            >
                <Volume2
                    class="h-5 w-5 {audioPlayer.isPlaying(
                        `quiz-word-${targetWord.id}`,
                    )
                        ? 'text-primary animate-pulse'
                        : 'text-muted-foreground'}"
                />
            </button>
        </div>
    </div>

    <!-- Options List -->
    <QuizOptions
        options={question.options}
        {selectedOptionId}
        {onSelect}
        {disabled}
    />
</div>
