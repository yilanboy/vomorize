<script lang="ts">
    import { onMount } from 'svelte';
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
        autoPlay = true,
        onSelect,
        disabled = false,
    }: {
        question: QuizQuestionPayload;
        vocabulary: VocabularyPayload;
        selectedOptionId?: number | null;
        autoPlay?: boolean;
        onSelect: (option: QuizOptionPayload) => void;
        disabled?: boolean;
    } = $props();

    const isAnswered = $derived(selectedOptionId !== null);
    const audioUrl = $derived(
        question.audio_url || vocabulary.word_audio_url || '',
    );
    const audioPlaying = $derived(
        audioPlayer.isPlaying(`quiz-audio-${vocabulary.id}`),
    );

    function playAudio(): void {
        if (audioUrl) {
            void audioPlayer.play(audioUrl, `quiz-audio-${vocabulary.id}`);
        }
    }

    onMount(() => {
        if (autoPlay && audioUrl) {
            playAudio();
        }
    });
</script>

<div
    class="border-border/80 bg-card mx-auto flex h-[560px] w-full max-w-xl flex-col justify-between rounded-2xl border p-6 shadow-2xs sm:h-[580px] sm:p-8"
>
    <!-- Card Header / Stem: Fixed height across all quiz cards -->
    <div class="flex h-[170px] shrink-0 flex-col sm:h-[180px]">
        <!-- Top Badges -->
        <div class="flex shrink-0 items-center">
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200/80 bg-blue-50/80 px-3 py-0.5 text-sm font-semibold tracking-wide text-blue-700 dark:border-blue-800/60 dark:bg-blue-950/50 dark:text-blue-300"
            >
                <span class="size-2 animate-pulse rounded-full bg-blue-600"
                ></span>
                <span>{t('ui.quiz.question_types.audio_to_definition')}</span>
            </span>
        </div>

        <!-- Speaker Prompt & Post-Answer Reveal -->
        <div
            class="flex flex-1 flex-col items-center justify-start gap-3 pt-4 sm:gap-3.5 sm:pt-5"
        >
            <button
                type="button"
                class={{
                    'relative flex size-15 shrink-0 items-center justify-center rounded-2xl border transition-all duration-200 active:scale-95 sm:size-16': true,
                    'scale-105 border-blue-600 bg-blue-600 text-white shadow-md ring-4 shadow-blue-500/25 ring-blue-500/20':
                        audioPlaying,
                    'animate-pulse border-blue-600/50 bg-blue-50/80 text-blue-600 dark:border-blue-700/60 dark:bg-blue-950/40 dark:text-blue-300':
                        !audioPlaying && !autoPlay && !isAnswered,
                    'border-border/70 bg-muted/40 hover:bg-muted text-foreground dark:bg-muted/30 hover:border-blue-300 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:text-blue-300':
                        !audioPlaying && (autoPlay || isAnswered),
                }}
                onclick={playAudio}
                aria-label={audioPlaying
                    ? t('ui.quiz.audio_prompt.playing')
                    : t('ui.quiz.audio_prompt.play_hint')}
                aria-pressed={audioPlaying}
                title={t('ui.quiz.audio_prompt.play_hint')}
            >
                <Volume2
                    class={{
                        'size-7 sm:size-8': true,
                        'animate-pulse': audioPlaying,
                    }}
                />
            </button>

            <!-- Answer Reveal: Reserved height below fixed speaker button to prevent shifting -->
            <div
                class={{
                    'flex min-h-10 flex-wrap items-baseline justify-center gap-x-2.5 gap-y-1 transition-opacity duration-150': true,
                    'visible opacity-100': isAnswered,
                    'invisible opacity-0 select-none': !isAnswered,
                }}
                aria-hidden={!isAnswered}
            >
                <span
                    class="text-foreground text-2xl font-bold tracking-tight sm:text-3xl"
                >
                    {vocabulary.word}
                </span>
                {#if vocabulary.pronunciation}
                    <span
                        class="text-muted-foreground font-mono text-sm sm:text-base"
                    >
                        {vocabulary.pronunciation}
                    </span>
                {/if}
                {#if vocabulary.part_of_speech}
                    <span
                        class="border-border/80 bg-muted/70 text-foreground/75 inline-flex shrink-0 items-center self-center rounded-md border px-2 py-0.5 text-sm leading-tight font-semibold italic"
                    >
                        {vocabulary.part_of_speech}
                    </span>
                {/if}
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
