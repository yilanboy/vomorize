<script lang="ts">
    import AudioButton from '@/components/AudioButton.svelte';
    import LargeAudioButton from '@/components/LargeAudioButton.svelte';
    import { playAudio, playCorrectSound, playWrongSound } from '@/lib/audio.svelte';
    import { answerFor, definitionOf } from '@/lib/groupQuiz';
    import type { QuizQuestion, QuizVocabulary } from '@/lib/groupQuiz';
    import { translations } from '@/lib/locale.svelte';

    let {
        question,
        answered = false,
        selectedId = null,
        onAnswer,
    } = $props<{
        question: QuizQuestion;
        answered?: boolean;
        selectedId?: number | null;
        onAnswer: (vocabularyId: number, correct: boolean) => void;
    }>();

    let t = $derived(translations());
    let prompt = $derived.by(() => {
        if (question.type === 'word_to_translation') {
            return t['quiz_pick_translation'];
        } else if (question.type === 'translation_to_word') {
            return t['quiz_pick_word'];
        } else {
            return t['quiz_listen_pick_translation'];
        }
    });

    $effect(() => {
        if (question.type === 'audio_to_translation') {
            void playAudio(question.vocabulary.audio_url);
        }
    });

    /**
     * The correct option is the question's own vocabulary, so correctness is an identity
     * comparison rather than a comparison of display text that a language change would move.
     */
    function select(option: QuizVocabulary): void {
        if (answered) {
            return;
        }

        const correct = option.id === question.vocabulary.id;

        if (correct) {
            playCorrectSound();
        } else {
            playWrongSound();
        }

        onAnswer(option.id, correct);
    }
</script>

<div
    class="w-full space-y-6 rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
>
    <!--
        Whatever the question asks with — a word, a definition, or a sound — is centred at the top
        of the card, and the region carries a floor tall enough for the tallest of the three, so the
        first answer option starts at the same height whichever type renders. The floor allows for
        three wrapped lines at heading size: the longest definition in the catalogue is 23 characters
        and the longest word 27, either of which wraps that far in a phone-width card.

        The listening question deliberately shows nothing but its replay button. Naming the word —
        or its pronunciation, or its part of speech — would answer the question it is asking.
    -->
    <div
        data-test="question-prompt"
        data-question-type={question.type}
        class="flex min-h-40 flex-col items-center justify-center gap-6 text-center"
    >
        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">{prompt}</p>

        {#if question.type === 'word_to_translation'}
            <h2 class="text-4xl font-bold wrap-break-word text-zinc-900 dark:text-zinc-50">
                {question.vocabulary.word}
            </h2>
            <AudioButton url={question.vocabulary.audio_url} />
        {:else if question.type === 'translation_to_word'}
            <h2 class="text-4xl font-bold wrap-break-word text-zinc-900 dark:text-zinc-50">
                {definitionOf(question.vocabulary)}
            </h2>
        {:else}
            <LargeAudioButton url={question.vocabulary.audio_url} />
        {/if}
    </div>

    <div class="space-y-3">
        <!--
            Keyed by vocabulary rather than by the rendered text, so a language change swaps each
            option's label in place instead of tearing down and rebuilding the list. Two words in
            a group sharing a definition also no longer collide on the same key.
        -->
        {#each question.options as option (option.id)}
            {@const answeredCorrect = answered && option.id === question.vocabulary.id}
            {@const answeredWrong =
                answered && selectedId === option.id && option.id !== question.vocabulary.id}
            <button
                type="button"
                onclick={() => select(option)}
                aria-disabled={answered}
                class={{
                    'w-full rounded-xl border p-4 text-left text-sm font-medium transition': true,
                    'border-emerald-500 bg-emerald-500/10 font-bold text-emerald-700 dark:text-emerald-300':
                        answeredCorrect,
                    'border-rose-500 bg-rose-500/10 text-rose-700 dark:text-rose-300':
                        answeredWrong,
                    'border-zinc-200 bg-zinc-100 hover:border-zinc-400 hover:bg-zinc-200 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-600 dark:hover:bg-zinc-800':
                        !answered,
                }}
            >
                {answerFor(option, question.type)}
            </button>
        {/each}
    </div>
</div>
