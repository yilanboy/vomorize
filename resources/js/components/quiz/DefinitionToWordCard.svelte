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
    class="border-border/80 bg-card mx-auto flex w-full max-w-xl flex-col gap-6 rounded-2xl border p-6 shadow-sm sm:p-8"
>
    <!-- Card Header / Stem -->
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span
                class="bg-primary/10 text-primary border-primary/20 inline-flex items-center rounded-full border px-3 py-1 font-mono text-sm font-semibold"
            >
                {t('ui.quiz.question_types.definition_to_word')}
            </span>

            <span
                class="bg-secondary text-secondary-foreground inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-medium"
            >
                {vocabulary.part_of_speech}
            </span>
        </div>

        <div class="flex flex-col gap-2">
            <h2
                class="text-foreground text-2xl font-bold tracking-tight sm:text-3xl"
            >
                {question.stem}
            </h2>

            <div
                class="flex min-h-[50px] items-center rounded-xl border p-3 transition-opacity duration-150 {selectedOptionId !==
                null
                    ? 'border-border/60 bg-muted/50 visible opacity-100'
                    : 'invisible border-transparent bg-transparent opacity-0 select-none'}"
                aria-hidden={selectedOptionId === null}
            >
                <div class="flex items-baseline gap-2">
                    <span class="text-foreground text-lg font-bold">
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
    <QuizOptions
        options={question.options}
        correctOptionId={question.correct_option_id}
        quizType={question.quiz_type}
        {selectedOptionId}
        {onSelect}
        {disabled}
    />
</div>
