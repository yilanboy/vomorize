<script lang="ts">
    import { onMount } from 'svelte';
    import { on } from 'svelte/events';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Delete from '@lucide/svelte/icons/delete';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import { Button } from '@/components/ui/button';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type {
        SpellingQuestionPayload,
        SpellingTile,
        VocabularyPayload,
    } from '@/types/quiz';

    let {
        question,
        vocabulary,
        autoPlay = true,
        onResult,
        onNext,
        disabled = false,
    }: {
        question: SpellingQuestionPayload;
        vocabulary: VocabularyPayload;
        autoPlay?: boolean;
        onResult: (isCorrect: boolean) => void;
        onNext: () => void;
        disabled?: boolean;
    } = $props();

    let filledSlots = $state<Record<number, number>>({});
    let isAnswered = $state(false);
    let isCorrect = $state<boolean | null>(null);
    let isShaking = $state(false);

    const usedSlotIndices = $derived(new Set(Object.values(filledSlots)));

    const blankSlots = $derived(
        question.slots
            .map((slot, index) => ({ ...slot, index }))
            .filter((slot) => slot.is_blank),
    );

    const nextEmptyBlankSlot = $derived(
        blankSlots.find((slot) => filledSlots[slot.index] === undefined),
    );

    const audioUrl = $derived(
        question.audio_url || vocabulary.word_audio_url || '',
    );

    const isAudioPlaying = $derived(
        audioPlayer.isPlaying(`quiz-word-${vocabulary.id}`),
    );

    const slotDensity = $derived.by(() => {
        const count = question.slots.length;
        if (count <= 6) {
            return {
                gap: 'gap-2 sm:gap-2.5',
                size: 'size-11 sm:size-12 text-xl',
            };
        }
        if (count <= 8) {
            return {
                gap: 'gap-1.5 sm:gap-2',
                size: 'size-10 sm:size-11 text-lg sm:text-xl',
            };
        }
        if (count <= 11) {
            return {
                gap: 'gap-1 sm:gap-1.5',
                size: 'h-10 w-7 sm:size-10 text-base sm:text-lg',
            };
        }
        return {
            gap: 'gap-0.5 sm:gap-1',
            size: 'h-9 w-6 sm:h-10 sm:w-8 text-sm sm:text-base',
        };
    });

    function playAudio(): void {
        if (audioUrl) {
            void audioPlayer.play(audioUrl, `quiz-word-${vocabulary.id}`);
        }
    }

    function handleTileClick(tile: SpellingTile): void {
        if (disabled || isAnswered || usedSlotIndices.has(tile.slot_index)) {
            return;
        }

        const targetSlot = nextEmptyBlankSlot;
        if (!targetSlot) {
            return;
        }

        filledSlots = {
            ...filledSlots,
            [targetSlot.index]: tile.slot_index,
        };

        if (Object.keys(filledSlots).length === blankSlots.length) {
            validateSubmission();
        }
    }

    function handleSlotClick(slotIndex: number): void {
        if (disabled || isAnswered) {
            return;
        }

        if (filledSlots[slotIndex] !== undefined) {
            const updated = { ...filledSlots };
            delete updated[slotIndex];
            filledSlots = updated;
        }
    }

    function handleBackspace(): void {
        if (disabled || isAnswered) {
            return;
        }

        const filledBlankSlots = blankSlots.filter(
            (slot) => filledSlots[slot.index] !== undefined,
        );

        if (filledBlankSlots.length > 0) {
            const lastFilled = filledBlankSlots[filledBlankSlots.length - 1];
            const updated = { ...filledSlots };
            delete updated[lastFilled.index];
            filledSlots = updated;
        }
    }

    function handleClear(): void {
        if (disabled || isAnswered) {
            return;
        }

        filledSlots = {};
    }

    function validateSubmission(): void {
        if (isAnswered) {
            return;
        }

        const correct = blankSlots.every((slot) => {
            const assignedTileSlotIndex = filledSlots[slot.index];
            const tile = question.tiles.find(
                (t) => t.slot_index === assignedTileSlotIndex,
            );
            return tile && tile.char === slot.char;
        });

        isAnswered = true;
        isCorrect = correct;

        if (!correct) {
            isShaking = true;
            setTimeout(() => {
                isShaking = false;
            }, 400);
        }

        onResult(correct);
    }

    onMount(() => {
        if (autoPlay && audioUrl) {
            playAudio();
        }

        function handleKeyDown(event: KeyboardEvent): void {
            const target = event.target as HTMLElement | null;
            if (
                target &&
                (target.tagName === 'INPUT' ||
                    target.tagName === 'TEXTAREA' ||
                    target.isContentEditable)
            ) {
                return;
            }

            if (event.key === ' ' || event.code === 'Space') {
                event.preventDefault();
                playAudio();
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                if (isAnswered && !isCorrect) {
                    onNext();
                }
                return;
            }

            if (event.key === 'Backspace') {
                event.preventDefault();
                handleBackspace();
                return;
            }

            const keyChar = event.key.toLowerCase();
            if (/^[a-z]$/.test(keyChar)) {
                event.preventDefault();
                if (isAnswered || disabled) {
                    return;
                }

                const availableTile = question.tiles.find(
                    (tile) =>
                        tile.char === keyChar &&
                        !usedSlotIndices.has(tile.slot_index),
                );

                if (availableTile) {
                    handleTileClick(availableTile);
                }
            }
        }

        return on(window, 'keydown', handleKeyDown);
    });
</script>

<div
    class="border-border/80 bg-card mx-auto flex h-[560px] w-full max-w-xl flex-col justify-between rounded-2xl border px-3.5 py-6 shadow-2xs sm:h-[580px] sm:p-8"
>
    <!-- Header / Stem -->
    <div class="flex h-[170px] shrink-0 flex-col justify-between sm:h-[180px]">
        <!-- Top Badges -->
        <div class="flex items-center justify-between">
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200/80 bg-blue-50/80 px-3 py-0.5 text-sm font-semibold tracking-wide text-blue-700 dark:border-blue-800/60 dark:bg-blue-950/50 dark:text-blue-300"
            >
                <span class="size-2 animate-pulse rounded-full bg-blue-600"
                ></span>
                <span>{t('ui.quiz.spelling.badge')}</span>
            </span>

            {#if vocabulary.part_of_speech}
                <span
                    class="bg-muted text-foreground/80 rounded-md px-2.5 py-0.5 text-sm font-semibold"
                >
                    {vocabulary.part_of_speech}
                </span>
            {/if}
        </div>

        <!-- Definition, Pronunciation, & Audio Trigger -->
        <div class="flex flex-1 items-center justify-between gap-4 py-2">
            <div class="flex flex-col gap-1.5">
                <h2
                    class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
                >
                    {question.stem}
                </h2>

                {#if vocabulary.pronunciation}
                    <span class="text-muted-foreground font-mono text-base">
                        {vocabulary.pronunciation}
                    </span>
                {/if}
            </div>

            <button
                type="button"
                class={{
                    'flex size-11 cursor-pointer items-center justify-center rounded-xl border transition-all': true,
                    'border-primary bg-primary text-white shadow-md':
                        isAudioPlaying,
                    'border-border/80 bg-muted/60 text-foreground hover:bg-muted':
                        !isAudioPlaying,
                }}
                onclick={playAudio}
                title={t('ui.quiz.spelling.play_audio_hint')}
            >
                <Volume2 class="size-5" />
            </button>
        </div>
    </div>

    <!-- Center Interactive Area: Slots and Error Feedback -->
    <div class="my-auto flex flex-col items-center justify-center gap-4 py-4">
        <!-- Letter Slots -->
        <div
            class={{
                'flex max-w-full flex-nowrap items-center justify-center overflow-x-auto py-1': true,
                [slotDensity.gap]: true,
            }}
        >
            {#each question.slots as slot, index (index)}
                {@const assignedTileIndex = filledSlots[index]}
                {@const assignedTile =
                    assignedTileIndex !== undefined
                        ? question.tiles.find(
                              (t) => t.slot_index === assignedTileIndex,
                          )
                        : null}
                {@const isSlotCorrect =
                    assignedTile != null && assignedTile.char === slot.char}

                {#if !slot.is_blank}
                    <!-- Static hint or separator slot -->
                    <div
                        class={{
                            'border-border/60 bg-muted/40 text-foreground flex shrink-0 items-center justify-center rounded-xl border font-mono font-bold select-none': true,
                            [slotDensity.size]: true,
                        }}
                    >
                        {slot.char}
                    </div>
                {:else}
                    <!-- Interactive blank slot -->
                    <button
                        type="button"
                        class={{
                            'flex shrink-0 items-center justify-center rounded-xl border font-mono font-bold transition-all select-none': true,
                            [slotDensity.size]: true,
                            'border-border/80 bg-background text-muted-foreground/40 border-dashed':
                                !assignedTile && !isAnswered,
                            'border-primary/80 bg-primary/5 text-foreground hover:border-destructive hover:bg-destructive/10 cursor-pointer shadow-xs':
                                assignedTile && !isAnswered,
                            'border-emerald-600 bg-emerald-50 text-emerald-700 dark:border-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-300':
                                isAnswered && isSlotCorrect,
                            'border-red-600 bg-red-50 text-red-700 dark:border-red-500 dark:bg-red-950/40 dark:text-red-300':
                                isAnswered && !isSlotCorrect,
                            'animate-shake':
                                isShaking && isAnswered && !isSlotCorrect,
                        }}
                        onclick={() => handleSlotClick(index)}
                        disabled={isAnswered || disabled || !assignedTile}
                    >
                        {assignedTile ? assignedTile.char : ''}
                    </button>
                {/if}
            {/each}
        </div>

        <!-- Wrong Answer Reveal & Next Button -->
        {#if isAnswered && !isCorrect}
            <div class="flex flex-col items-center gap-2.5 pt-1 text-center">
                <div
                    class="text-sm font-semibold text-red-600 dark:text-red-400"
                >
                    <span>{t('ui.quiz.spelling.correct_answer')}: </span>
                    <span class="font-mono font-bold tracking-wider uppercase">
                        {question.target_word}
                    </span>
                </div>

                <Button
                    size="sm"
                    class="h-10 cursor-pointer gap-1.5 px-5 text-sm font-semibold"
                    onclick={onNext}
                >
                    <span>{t('ui.quiz.spelling.next')}</span>
                    <ArrowRight class="size-4" />
                </Button>
            </div>
        {/if}
    </div>

    <!-- Bottom Controls Area: Letter Tiles Pool & Actions -->
    <div class="space-y-4 pt-2">
        <!-- Letter Tiles Pool -->
        <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3">
            {#each question.tiles as tile (tile.slot_index)}
                {@const isUsed = usedSlotIndices.has(tile.slot_index)}
                <button
                    type="button"
                    class={{
                        'flex min-h-[48px] min-w-[48px] items-center justify-center rounded-xl border font-mono text-xl font-bold transition-all select-none': true,
                        'border-border/80 bg-card hover:bg-muted text-foreground cursor-pointer shadow-xs active:scale-95':
                            !isUsed && !isAnswered,
                        'bg-muted/40 text-muted-foreground pointer-events-none border-dashed opacity-30':
                            isUsed || isAnswered,
                    }}
                    onclick={() => handleTileClick(tile)}
                    disabled={isUsed || isAnswered || disabled}
                >
                    {tile.char}
                </button>
            {/each}
        </div>

        <!-- Bottom Action Bar -->
        <div
            class="border-border/80 flex items-center justify-between border-t pt-3"
        >
            <span
                class="text-muted-foreground hidden text-sm font-medium sm:inline"
            >
                {t('ui.quiz.spelling.keyboard_hint')}
            </span>

            <div class="ml-auto flex items-center gap-2">
                <button
                    type="button"
                    class="hover:bg-muted text-muted-foreground hover:text-foreground border-border/80 inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border px-3 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                    onclick={handleBackspace}
                    disabled={Object.keys(filledSlots).length === 0 ||
                        isAnswered ||
                        disabled}
                >
                    <Delete class="size-4" />
                    <span>{t('ui.quiz.spelling.backspace')}</span>
                </button>

                <button
                    type="button"
                    class="hover:bg-muted text-muted-foreground hover:text-foreground border-border/80 inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border px-3 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                    onclick={handleClear}
                    disabled={Object.keys(filledSlots).length === 0 ||
                        isAnswered ||
                        disabled}
                >
                    <RotateCcw class="size-4" />
                    <span>{t('ui.quiz.spelling.clear')}</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes shake {
        0%,
        100% {
            transform: translateX(0);
        }
        20%,
        60% {
            transform: translateX(-6px);
        }
        40%,
        80% {
            transform: translateX(6px);
        }
    }

    .animate-shake {
        animation: shake 0.4s ease-in-out;
    }
</style>
