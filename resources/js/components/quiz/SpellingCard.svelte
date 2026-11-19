<script lang="ts">
    import { onMount } from 'svelte';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import Delete from '@lucide/svelte/icons/delete';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import TactileTile from '@/components/TactileTile.svelte';
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
        disabled = false,
    }: {
        question: SpellingQuestionPayload;
        vocabulary: VocabularyPayload;
        autoPlay?: boolean;
        onResult: (isCorrect: boolean) => void;
        disabled?: boolean;
    } = $props();

    let filledSlots = $state<Record<number, number>>({});
    let isAnswered = $state(false);
    let isCorrect = $state<boolean | null>(null);

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
        if (count <= 7) {
            return {
                gap: 'gap-1.5 sm:gap-2',
                size: 'h-11 w-7 text-3xl sm:h-12 sm:w-8 sm:text-4xl',
            };
        }
        if (count <= 10) {
            return {
                gap: 'gap-1 sm:gap-1.5',
                size: 'h-10 w-6 text-2xl sm:h-11 sm:w-7 sm:text-3xl',
            };
        }
        if (count <= 14) {
            return {
                gap: 'gap-0.5 sm:gap-1',
                size: 'h-9 w-5 text-xl sm:h-10 sm:w-6 sm:text-2xl',
            };
        }
        return {
            gap: 'gap-0.5 sm:gap-1',
            size: 'h-9 w-[18px] text-lg sm:h-10 sm:w-5 sm:text-xl',
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
        onResult(correct);
    }

    onMount(() => {
        if (autoPlay && audioUrl) {
            playAudio();
        }
    });
</script>

<div
    class="border-border/80 bg-card mx-auto flex h-[580px] w-full max-w-xl flex-col justify-between rounded-2xl border px-3 py-6 shadow-sm transition-all sm:h-[600px] sm:p-8"
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
                    'flex size-11 cursor-pointer items-center justify-center rounded-xl border transition-all select-none': true,
                    'border-primary bg-primary text-white shadow-md':
                        isAudioPlaying,
                    'border-border/80 bg-muted/60 text-foreground hover:bg-muted active:scale-95':
                        !isAudioPlaying,
                }}
                onclick={playAudio}
                title={t('ui.quiz.spelling.play_audio_hint')}
            >
                <Volume2
                    class={{ 'size-5': true, 'animate-pulse': isAudioPlaying }}
                />
            </button>
        </div>
    </div>

    <!-- Center Interactive Area: Slots and Zero-Jump Feedback -->
    <div
        class="my-auto flex flex-col items-center justify-center gap-4 py-2 sm:py-3"
    >
        <!-- Letter Slots -->
        <div
            class={{
                'flex max-w-full [scrollbar-width:none] flex-nowrap items-center justify-center overflow-x-auto py-1.5 [&::-webkit-scrollbar]:hidden': true,
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
                {@const isTargetCursor =
                    !isAnswered &&
                    !assignedTile &&
                    nextEmptyBlankSlot?.index === index}

                {#if !slot.is_blank}
                    <!-- Static hint or separator slot (Anchor) -->
                    <div
                        class={{
                            'flex shrink-0 items-center justify-center border-b-2 border-transparent font-mono font-bold transition-colors duration-150 select-none': true,
                            [slotDensity.size]: true,
                            'text-emerald-600 dark:text-emerald-400':
                                isAnswered && isCorrect,
                            'text-foreground': !(isAnswered && isCorrect),
                        }}
                    >
                        {slot.char}
                    </div>
                {:else}
                    <!-- Interactive blank slot (Underline) -->
                    <button
                        type="button"
                        class={{
                            'focus-visible:outline-primary/70 flex shrink-0 items-center justify-center border-b-2 font-mono font-bold transition-all duration-150 select-none focus-visible:outline-2 focus-visible:outline-offset-4': true,
                            [slotDensity.size]: true,
                            'border-zinc-400/70 dark:border-zinc-600':
                                !assignedTile && !isAnswered && !isTargetCursor,
                            'border-primary dark:border-blue-400':
                                isTargetCursor,
                            'border-primary/70 text-primary hover:border-destructive hover:text-destructive cursor-pointer active:scale-95 dark:border-blue-400/70 dark:text-blue-400':
                                assignedTile && !isAnswered,
                            'border-transparent text-emerald-600 dark:text-emerald-400':
                                isAnswered && isCorrect,
                            'border-emerald-600/60 text-emerald-600 dark:border-emerald-400/60 dark:text-emerald-400':
                                isAnswered && !isCorrect && isSlotCorrect,
                            'border-red-600 text-red-600 dark:border-red-400 dark:text-red-400':
                                isAnswered && !isCorrect && !isSlotCorrect,
                        }}
                        onclick={() => handleSlotClick(index)}
                        disabled={isAnswered || disabled || !assignedTile}
                        title={assignedTile && !isAnswered
                            ? t('ui.quiz.spelling.touch_hint')
                            : undefined}
                    >
                        {#if assignedTile}
                            {assignedTile.char}
                        {:else if isTargetCursor}
                            <span
                                class="bg-primary/80 h-5 w-0.5 animate-pulse rounded-full sm:h-6 dark:bg-blue-400/80"
                                aria-hidden="true"
                            ></span>
                        {/if}
                    </button>
                {/if}
            {/each}
        </div>

        <!-- Stable Feedback Bar (Fixed Height to Prevent Layout Jumping) -->
        <div
            class="flex h-14 w-full max-w-md items-center justify-center px-1 sm:h-16"
        >
            {#if isAnswered && isCorrect}
                <!-- Correct submission banner -->
                <div
                    class="flex items-center gap-2 text-sm font-bold text-emerald-600 select-none dark:text-emerald-400"
                >
                    <CheckCircle2 class="size-5" />
                    <span>{t('ui.quiz.spelling.correct_banner')}</span>
                </div>
            {:else if isAnswered && !isCorrect}
                <!-- Wrong submission reveal (Pure visual answer contrast, no mid-card button) -->
                <div
                    class="flex h-full w-full items-center justify-center gap-2 rounded-xl border border-red-200/80 bg-red-50/90 px-4 py-2 text-center dark:border-red-900/60 dark:bg-red-950/40"
                >
                    <span
                        class="text-sm font-medium text-red-600/90 select-none dark:text-red-400/90"
                    >
                        {t('ui.quiz.spelling.correct_answer')}:
                    </span>
                    <span
                        class="font-mono text-base font-extrabold tracking-wider text-red-700 sm:text-lg dark:text-red-300"
                    >
                        {question.target_word}
                    </span>
                </div>
            {/if}
        </div>
    </div>

    <!-- Bottom Controls Area: Letter Tiles Pool & Actions -->
    <div class="space-y-4 pt-2">
        <!-- Letter Tiles Pool (Tactile Keycaps) -->
        <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3">
            {#each question.tiles as tile (tile.slot_index)}
                {@const isUsed = usedSlotIndices.has(tile.slot_index)}
                <TactileTile
                    depressed={isUsed || isAnswered}
                    class="size-[50px] shrink-0 font-mono text-xl font-extrabold sm:size-[54px] sm:text-2xl"
                    onclick={() => handleTileClick(tile)}
                    disabled={isUsed || isAnswered || disabled}
                >
                    {tile.char}
                </TactileTile>
            {/each}
        </div>

        <!-- Bottom Action Bar -->
        <div
            class="border-border/80 flex h-14 items-center justify-end border-t pt-2"
        >
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class={{
                        'border-border/80 inline-flex h-10 cursor-pointer items-center gap-1.5 rounded-xl border px-3 text-sm font-semibold transition-all select-none': true,
                        'bg-card text-foreground hover:bg-muted shadow-[0_2px_0_0_rgba(0,0,0,0.06)] active:translate-y-0.5 active:shadow-none dark:shadow-[0_2px_0_0_rgba(255,255,255,0.05)]':
                            Object.keys(filledSlots).length > 0 &&
                            !isAnswered &&
                            !disabled,
                        'text-muted-foreground/30 cursor-not-allowed border-dashed opacity-40 shadow-none':
                            Object.keys(filledSlots).length === 0 ||
                            isAnswered ||
                            disabled,
                    }}
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
                    class={{
                        'border-border/80 inline-flex h-10 cursor-pointer items-center gap-1.5 rounded-xl border px-3 text-sm font-semibold transition-all select-none': true,
                        'bg-card text-foreground hover:bg-muted shadow-[0_2px_0_0_rgba(0,0,0,0.06)] active:translate-y-0.5 active:shadow-none dark:shadow-[0_2px_0_0_rgba(255,255,255,0.05)]':
                            Object.keys(filledSlots).length > 0 &&
                            !isAnswered &&
                            !disabled,
                        'text-muted-foreground/30 cursor-not-allowed border-dashed opacity-40 shadow-none':
                            Object.keys(filledSlots).length === 0 ||
                            isAnswered ||
                            disabled,
                    }}
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
