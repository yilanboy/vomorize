<script lang="ts">
    import { onMount } from 'svelte';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import QuizOptions from '@/components/quiz/QuizOptions.svelte';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type { QuizOptionPayload, QuizQuestionPayload } from '@/types/quiz';

    let {
        question,
        selectedOptionId = null,
        autoPlay = true,
        onSelect,
        disabled = false,
    }: {
        question: QuizQuestionPayload;
        selectedOptionId?: number | null;
        autoPlay?: boolean;
        onSelect: (option: QuizOptionPayload) => void;
        disabled?: boolean;
    } = $props();

    const targetWord = $derived(question.target_word);
    const audioPlaying = $derived(
        audioPlayer.isPlaying(`quiz-audio-${targetWord.id}`),
    );

    function playAudio() {
        if (targetWord.word_audio_url) {
            void audioPlayer.play(
                targetWord.word_audio_url,
                `quiz-audio-${targetWord.id}`,
            );
        }
    }

    onMount(() => {
        if (autoPlay && targetWord.word_audio_url) {
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
    class="border-border/80 bg-card mx-auto flex w-full max-w-xl flex-col gap-6 rounded-2xl border p-6 shadow-sm sm:p-8"
>
    <!-- Card Header / Stem -->
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <span
                class="bg-primary/10 text-primary border-primary/20 inline-flex items-center rounded-full border px-3 py-1 font-mono text-sm font-semibold"
            >
                {t('ui.quiz.question_types.audio_to_definition')}
            </span>

            {#if selectedOptionId !== null}
                <span
                    class="bg-secondary text-secondary-foreground inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-medium"
                >
                    {targetWord.part_of_speech}
                </span>
            {/if}
        </div>

        <!-- Speaker Prompt -->
        <div class="flex flex-col items-center justify-center gap-3 py-2">
            <button
                type="button"
                class="relative flex h-20 w-20 items-center justify-center rounded-2xl border transition-all duration-300 {audioPlaying
                    ? 'border-primary bg-primary text-primary-foreground shadow-primary/25 scale-105 shadow-lg'
                    : !autoPlay && selectedOptionId === null
                      ? 'border-primary/50 bg-primary/10 text-primary animate-pulse'
                      : 'border-border/80 bg-muted/60 hover:bg-muted text-foreground hover:scale-105 active:scale-95'}"
                onclick={playAudio}
                title={t('ui.quiz.audio_prompt.play_hint')}
            >
                <Volume2 class="h-9 w-9" />
            </button>

            <span class="text-muted-foreground text-sm font-medium">
                {audioPlaying
                    ? t('ui.quiz.audio_prompt.playing')
                    : t('ui.quiz.audio_prompt.play_hint')}
            </span>

            {#if selectedOptionId !== null}
                <div class="flex items-baseline gap-2 pt-1">
                    <span class="text-foreground text-xl font-bold">
                        {targetWord.word}
                    </span>
                    <span class="text-muted-foreground font-mono text-base">
                        {targetWord.pronunciation}
                    </span>
                </div>
            {/if}
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
