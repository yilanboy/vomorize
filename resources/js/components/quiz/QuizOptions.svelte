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

    function handleSelect(option: QuizOptionPayload) {
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

<div class="flex flex-col gap-3">
    {#each options as option, index (option.id)}
        {@const isSelected = selectedOptionId === option.id}
        {@const isAnswered = selectedOptionId !== null}
        {@const isCorrect = option.id === correctOptionId}
        {@const isWrongSelection = isSelected && !isCorrect}

        <button
            type="button"
            class={{
                'group relative flex min-h-14 w-full items-center justify-between gap-3 rounded-xl border p-4 text-left transition-all duration-200': true,
                'border-emerald-500 bg-emerald-500/10 text-emerald-900 shadow-sm dark:bg-emerald-950/40 dark:text-emerald-200':
                    isAnswered && isCorrect,
                'border-red-500 bg-red-500/10 text-red-900 shadow-sm dark:bg-red-950/40 dark:text-red-200':
                    isAnswered && !isCorrect && isWrongSelection,
                'border-border/50 opacity-40':
                    isAnswered && !isCorrect && !isWrongSelection,
                'border-border/70 hover:border-primary/50 hover:bg-muted/50 bg-card active:scale-[0.99]':
                    !isAnswered,
            }}
            onclick={() => handleSelect(option)}
            disabled={disabled || isAnswered}
        >
            <span class="flex flex-1 items-center gap-3.5">
                <span
                    class={{
                        'flex size-7 shrink-0 items-center justify-center rounded-lg border border-transparent font-mono text-sm font-semibold transition-colors': true,
                        'bg-emerald-500 text-white dark:bg-emerald-600':
                            isAnswered && isCorrect,
                        'bg-red-500 text-white dark:bg-red-600':
                            isAnswered && !isCorrect && isWrongSelection,
                        'bg-muted text-muted-foreground':
                            isAnswered && !isCorrect && !isWrongSelection,
                        'group-hover:border-primary/40 bg-muted/80 text-foreground group-hover:bg-primary group-hover:text-primary-foreground':
                            !isAnswered,
                    }}
                >
                    {index + 1}
                </span>

                <span class="flex flex-1 flex-col justify-center">
                    <span
                        class={{
                            'text-base leading-snug font-medium': true,
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

    <p class="text-muted-foreground text-center text-sm">
        {t('ui.quiz.options.keyboard_hint')}
    </p>
</div>
