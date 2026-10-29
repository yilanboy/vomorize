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
                playAudio();
            }
        }

        window.addEventListener('keydown', handleKeyDown);
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    });
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
                <span>{t('ui.quiz.question_types.audio_to_definition')}</span>
            </span>

            <span
                class={{
                    'bg-muted text-foreground/80 rounded-md px-2.5 py-0.5 text-sm font-semibold transition-opacity duration-150': true,
                    'visible opacity-100': selectedOptionId !== null,
                    'invisible opacity-0 select-none':
                        selectedOptionId === null,
                }}
                aria-hidden={selectedOptionId === null}
            >
                {vocabulary.part_of_speech}
            </span>
        </div>

        <!-- Speaker Prompt & Reveal Area -->
        <div
            class="flex flex-1 flex-col items-center justify-center gap-1.5 py-1"
        >
            <button
                type="button"
                class={{
                    'relative flex size-14 items-center justify-center rounded-2xl border transition-all duration-200 active:scale-95 sm:size-15': true,
                    'scale-105 border-blue-600 bg-blue-600 text-white shadow-md ring-4 shadow-blue-500/25 ring-blue-500/20':
                        audioPlaying,
                    'animate-pulse border-blue-600/50 bg-blue-50/80 text-blue-600 dark:border-blue-700/60 dark:bg-blue-950/40 dark:text-blue-300':
                        !audioPlaying && !autoPlay && selectedOptionId === null,
                    'border-border/70 bg-muted/40 hover:bg-muted text-foreground dark:bg-muted/30 hover:border-blue-300 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:text-blue-300':
                        !audioPlaying &&
                        (autoPlay || selectedOptionId !== null),
                }}
                onclick={playAudio}
                title={t('ui.quiz.audio_prompt.play_hint')}
            >
                <Volume2 class="size-7 sm:size-8" />
            </button>

            <!-- Play Status / Hint Text: Centered directly below the button in its own row -->
            <div class="flex h-5 items-center justify-center">
                <span
                    class="text-muted-foreground text-center text-sm font-medium"
                >
                    {audioPlaying
                        ? t('ui.quiz.audio_prompt.playing')
                        : t('ui.quiz.audio_prompt.play_hint')}
                </span>
            </div>

            <!-- Answer Reveal: Space is reserved with h-6 to prevent layout jumping -->
            <div
                class={{
                    'flex h-6 items-baseline gap-2.5 transition-opacity duration-150': true,
                    'visible opacity-100': selectedOptionId !== null,
                    'invisible opacity-0 select-none':
                        selectedOptionId === null,
                }}
                aria-hidden={selectedOptionId === null}
            >
                <span class="text-foreground text-base font-bold sm:text-lg">
                    {vocabulary.word}
                </span>
                <span class="text-muted-foreground font-mono text-sm">
                    {vocabulary.pronunciation}
                </span>
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
