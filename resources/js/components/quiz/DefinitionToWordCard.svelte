<script lang="ts">
    import QuizOptions from '@/components/quiz/QuizOptions.svelte';
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
                <span>{t('ui.quiz.question_types.definition_to_word')}</span>
            </span>
        </div>

        <!-- Definition Stem & Reveal Area -->
        <div
            class="flex flex-1 flex-col justify-start gap-2.5 pt-5 sm:gap-3 sm:pt-6"
        >
            <div class="flex min-h-11 items-center">
                <h2
                    class="text-foreground line-clamp-2 text-2xl font-extrabold tracking-tight sm:text-3xl"
                >
                    {question.stem}
                </h2>
            </div>

            <!-- Answer Reveal: Reserved height prevents any layout shifting -->
            <div
                class={{
                    'flex min-h-10 flex-wrap items-baseline gap-x-2.5 gap-y-1 transition-opacity duration-150': true,
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
