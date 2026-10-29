<script lang="ts">
    import { onMount } from 'svelte';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import XCircle from '@lucide/svelte/icons/x-circle';
    import { t } from '@/lib/i18n';
    import type { QuizOptionPayload, QuizType } from '@/types/quiz';

    let {
        options = [],
        correctOptionId,
        quizType = 'word_to_definition',
        selectedOptionId = null,
        onSelect,
        disabled = false,
    }: {
        options: QuizOptionPayload[];
        correctOptionId: number;
        quizType?: QuizType;
        selectedOptionId?: number | null;
        onSelect: (option: QuizOptionPayload) => void;
        disabled?: boolean;
    } = $props();

    function handleSelect(option: QuizOptionPayload): void {
        if (disabled || selectedOptionId !== null) {
            return;
        }
        onSelect(option);
    }

    onMount(() => {
        function handleKeyDown(event: KeyboardEvent) {
            if (disabled || selectedOptionId !== null) {
                return;
            }

            const target = event.target as HTMLElement | null;
            if (
                target &&
                (target.tagName === 'INPUT' ||
                    target.tagName === 'TEXTAREA' ||
                    target.isContentEditable)
            ) {
                return;
            }

            const key = event.key;
            if (['1', '2', '3', '4'].includes(key)) {
                const index = parseInt(key, 10) - 1;
                if (options[index]) {
                    event.preventDefault();
                    handleSelect(options[index]);
                }
            }
        }

        window.addEventListener('keydown', handleKeyDown);
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    });
</script>

<div class="flex flex-col gap-2.5 sm:gap-3">
    {#each options as option, index (option.id)}
        {@const isSelected = selectedOptionId === option.id}
        {@const isAnswered = selectedOptionId !== null}
        {@const isCorrect = option.id === correctOptionId}
        {@const isWrongSelection = isSelected && !isCorrect}

        <button
            type="button"
            class={{
                'group relative flex min-h-[52px] w-full items-center justify-between gap-3 rounded-xl border p-3.5 text-left transition-all duration-150 sm:min-h-[56px] sm:p-4': true,
                'border-emerald-600 bg-emerald-500/10 text-emerald-950 shadow-xs dark:border-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-200':
                    isAnswered && isCorrect,
                'border-red-600 bg-red-500/10 text-red-950 shadow-xs dark:border-red-500 dark:bg-red-950/40 dark:text-red-200':
                    isAnswered && !isCorrect && isWrongSelection,
                'border-border/40 bg-card/40 opacity-40 select-none':
                    isAnswered && !isCorrect && !isWrongSelection,
                'border-border/80 bg-card hover:border-blue-400 hover:bg-blue-50/40 active:scale-[0.99] dark:hover:border-blue-700 dark:hover:bg-blue-950/20':
                    !isAnswered,
            }}
            onclick={() => handleSelect(option)}
            disabled={disabled || isAnswered}
        >
            <span class="flex flex-1 items-center gap-3">
                <span
                    class={{
                        'flex size-7 shrink-0 items-center justify-center rounded-lg border font-mono text-sm font-semibold transition-colors': true,
                        'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-500 dark:bg-emerald-500':
                            isAnswered && isCorrect,
                        'border-red-600 bg-red-600 text-white dark:border-red-500 dark:bg-red-500':
                            isAnswered && !isCorrect && isWrongSelection,
                        'bg-muted text-muted-foreground border-transparent':
                            isAnswered && !isCorrect && !isWrongSelection,
                        'border-border/70 bg-muted/80 text-foreground group-hover:border-blue-600 group-hover:bg-blue-600 group-hover:text-white':
                            !isAnswered,
                    }}
                >
                    {index + 1}
                </span>

                <span class="flex flex-1 flex-col justify-center">
                    <span
                        class={{
                            'text-sm leading-snug font-medium sm:text-base': true,
                            'font-semibold':
                                isAnswered && (isCorrect || isWrongSelection),
                            'text-foreground': !isAnswered,
                        }}
                    >
                        {option.text}
                    </span>
                </span>
            </span>

            <span class="flex size-5 shrink-0 items-center justify-center">
                {#if isAnswered}
                    {#if isCorrect}
                        <CheckCircle2
                            class="size-5 text-emerald-600 dark:text-emerald-400"
                        />
                    {:else if isWrongSelection}
                        <XCircle
                            class="size-5 text-red-600 dark:text-red-400"
                        />
                    {/if}
                {/if}
            </span>
        </button>
    {/each}

    <p class="text-muted-foreground text-center text-sm font-medium">
        {t('ui.quiz.options.keyboard_hint')}
    </p>
</div>
