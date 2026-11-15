<script lang="ts">
    import QuizOptions from '@/components/quiz/QuizOptions.svelte';
    import { t } from '@/lib/i18n';
    import type {
        QuizOptionPayload,
        QuizQuestionPayload,
        SentenceSegment,
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
    const segments = $derived<SentenceSegment[]>(
        question.sentence_segments && question.sentence_segments.length > 0
            ? question.sentence_segments
            : [{ type: 'text', text: question.stem }],
    );
    const translation = $derived(
        question.sentence_translation || vocabulary.example_translation,
    );
</script>

<div
    class="border-border/80 bg-card mx-auto flex h-[560px] w-full max-w-xl flex-col justify-between rounded-2xl border p-6 shadow-2xs sm:h-[580px] sm:p-8"
>
    <!-- Card Header / Sentence Stem & Post-Answer Reveal -->
    <div class="flex h-[212px] shrink-0 flex-col justify-between sm:h-[222px]">
        <!-- Top Badges -->
        <div class="flex items-center justify-between">
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200/80 bg-blue-50/80 px-3 py-0.5 text-sm font-semibold tracking-wide text-blue-700 dark:border-blue-800/60 dark:bg-blue-950/50 dark:text-blue-300"
            >
                <span class="size-2 animate-pulse rounded-full bg-blue-600"
                ></span>
                <span>{t('ui.quiz.question_types.sentence_cloze')}</span>
            </span>

            <span
                class={{
                    'bg-muted text-foreground/80 rounded-md px-2.5 py-0.5 text-sm font-semibold transition-opacity duration-150': true,
                    'visible opacity-100': isAnswered,
                    'invisible opacity-0 select-none': !isAnswered,
                }}
                aria-hidden={!isAnswered}
            >
                {vocabulary.part_of_speech}
            </span>
        </div>

        <!-- Masked English Sentence -->
        <div class="flex flex-1 flex-col justify-center gap-2.5 py-1">
            <p
                class="text-foreground text-lg leading-relaxed font-semibold tracking-tight sm:text-xl"
                data-test="sentence-cloze-stem"
            >
                {#each segments as segment, idx (`${idx}-${segment.type}`)}
                    {#if segment.type === 'blank'}
                        <span
                            data-test="sentence-cloze-blank"
                            class={{
                                'mx-1 inline-flex min-w-20 items-center justify-center rounded-md border px-2.5 py-0.5 align-baseline font-semibold transition-colors duration-150': true,
                                'border-blue-600/50 bg-blue-50/80 font-mono tracking-widest text-blue-700 dark:border-blue-500/60 dark:bg-blue-950/40 dark:text-blue-300':
                                    !isAnswered,
                                'border-emerald-600/50 bg-emerald-500/15 text-emerald-700 dark:border-emerald-500/50 dark:bg-emerald-950/60 dark:text-emerald-300':
                                    isAnswered,
                            }}
                        >
                            {isAnswered ? segment.text : '_____'}
                        </span>
                    {:else}
                        <span>{segment.text}</span>
                    {/if}
                {/each}
            </p>

            <!-- Post-Answer Reveal: Hidden before answering, reserved height prevents layout shift -->
            <div
                data-test="sentence-cloze-reveal"
                class={{
                    'border-border/60 bg-muted/40 flex min-h-14 flex-col justify-center rounded-xl border px-3.5 py-2 transition-opacity duration-150': true,
                    'visible opacity-100': isAnswered,
                    'invisible border-transparent bg-transparent opacity-0 select-none':
                        !isAnswered,
                }}
                aria-hidden={!isAnswered}
            >
                <p
                    class="text-foreground/90 line-clamp-2 text-sm leading-snug font-medium"
                >
                    {translation}
                </p>
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
