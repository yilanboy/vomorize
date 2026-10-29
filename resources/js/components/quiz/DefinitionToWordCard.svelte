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
                <span>{t('ui.quiz.question_types.definition_to_word')}</span>
            </span>

            <span
                class="bg-muted text-foreground/80 rounded-md px-2.5 py-0.5 text-sm font-semibold"
            >
                {vocabulary.part_of_speech}
            </span>
        </div>

        <!-- Definition Stem & Reveal Area -->
        <div class="flex flex-1 flex-col justify-center gap-2 py-1">
            <h2
                class="text-foreground line-clamp-2 text-2xl font-bold tracking-tight sm:text-3xl"
            >
                {question.stem}
            </h2>

            <!-- Answer Reveal: Space is reserved with h-11 to prevent any layout shifting -->
            <div
                class={{
                    'border-border/60 bg-muted/40 flex h-11 items-center rounded-xl border px-3.5 transition-opacity duration-150': true,
                    'visible opacity-100': selectedOptionId !== null,
                    'invisible border-transparent bg-transparent opacity-0 select-none':
                        selectedOptionId === null,
                }}
                aria-hidden={selectedOptionId === null}
            >
                <div class="flex items-baseline gap-2.5">
                    <span
                        class="text-foreground text-base font-bold sm:text-lg"
                    >
                        {vocabulary.word}
                    </span>
                    <span class="text-muted-foreground font-mono text-sm">
                        {vocabulary.pronunciation}
                    </span>
                </div>
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
