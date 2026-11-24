<script lang="ts">
    import { Link, page, setLayoutProps } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Brain from '@lucide/svelte/icons/brain';
    import Check from '@lucide/svelte/icons/check';
    import Cloud from '@lucide/svelte/icons/cloud';
    import Eye from '@lucide/svelte/icons/eye';
    import EyeOff from '@lucide/svelte/icons/eye-off';
    import Layers from '@lucide/svelte/icons/layers';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Smartphone from '@lucide/svelte/icons/smartphone';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import { dashboard } from '@/routes';
    import { index as levelsIndex, show as levelsShow } from '@/routes/levels';

    setLayoutProps<{ transparentHeader?: boolean }>({
        transparentHeader: true,
    });

    interface LevelData {
        id: number;
        name: string;
        description: string;
        vocabularies_count?: number;
        groups_count?: number;
    }

    interface VocabularyData {
        id: number;
        word: string;
        part_of_speech: string;
        pronunciation: string;
        example_sentence: string;
        translation: {
            definition: string;
            example_translation: string;
        };
    }

    let {
        levels = [],
        vocabulary,
    }: {
        levels?: LevelData[];
        vocabulary?: VocabularyData | null;
    } = $props();

    const auth = $derived(page.props.auth);

    const activeVocab = $derived.by(() => {
        if (vocabulary?.word) {
            return {
                id: vocabulary.id,
                word: vocabulary.word,
                part_of_speech: vocabulary.part_of_speech,
                pronunciation: vocabulary.pronunciation,
                definition: vocabulary.translation?.definition ?? '',
                example_sentence: vocabulary.example_sentence,
                example_translation:
                    vocabulary.translation?.example_translation ?? '',
                hasAudio: Boolean(vocabulary.id),
            };
        }

        return {
            id: 0,
            word: t('ui.welcome.hero.fallback.word'),
            part_of_speech: t('ui.welcome.hero.fallback.part_of_speech'),
            pronunciation: t('ui.welcome.hero.fallback.pronunciation'),
            definition: t('ui.welcome.hero.fallback.definition'),
            example_sentence: t('ui.welcome.hero.fallback.example_sentence'),
            example_translation: t(
                'ui.welcome.hero.fallback.example_translation',
            ),
            hasAudio: false,
        };
    });

    const wordAudioUrl = $derived(
        activeVocab.hasAudio
            ? `https://assets.vomorize.com/vocabulary/${activeVocab.id}/word.mp3`
            : '',
    );
    const sentenceAudioUrl = $derived(
        activeVocab.hasAudio
            ? `https://assets.vomorize.com/vocabulary/${activeVocab.id}/sentence.mp3`
            : '',
    );

    function playWordAudio(): void {
        if (!wordAudioUrl) {
            return;
        }
        audioPlayer.stop();
        void audioPlayer.play(wordAudioUrl, 'hero-word');
    }

    function playSentenceAudio(): void {
        if (!sentenceAudioUrl) {
            return;
        }
        audioPlayer.stop();
        void audioPlayer.play(sentenceAudioUrl, 'hero-sentence');
    }

    onMount(() => {
        if (
            wordAudioUrl &&
            sentenceAudioUrl &&
            window.location.hostname.endsWith('vomorize.com')
        ) {
            void audioPlayer.preload([wordAudioUrl, sentenceAudioUrl]);
        }

        return () => {
            audioPlayer.stop();
        };
    });

    // Band 1: Hero Interactive Workbench
    type WorkbenchMode = 'card' | 'recall' | 'spelling';
    let workbenchMode = $state<WorkbenchMode>('card');

    // Recall mode state
    let recallRevealed = $state(false);
    let recallChoice = $state<'known' | 'review' | null>(null);

    function switchWorkbenchMode(mode: WorkbenchMode): void {
        workbenchMode = mode;
        if (mode === 'recall') {
            recallRevealed = false;
            recallChoice = null;
        } else if (mode === 'spelling') {
            filledSlots = {};
        }
    }

    // Spelling mode deterministic puzzle derived from activeVocab.word
    interface SpellingSlot {
        index: number;
        char: string;
        isBlank: boolean;
    }

    interface SpellingTile {
        tileId: number;
        slotIndex: number;
        char: string;
    }

    const spellingPuzzle = $derived.by(() => {
        const raw = activeVocab.word.trim();
        const chars = Array.from(raw);
        const alphaIndices: number[] = [];

        chars.forEach((ch, idx) => {
            if (/[a-zA-Z]/.test(ch)) {
                alphaIndices.push(idx);
            }
        });

        const blankSet = new Set<number>();
        if (alphaIndices.length > 0) {
            const candidatePool =
                alphaIndices.length >= 5
                    ? alphaIndices.slice(1, -1)
                    : alphaIndices;
            const targetCount = Math.min(
                4,
                Math.max(1, Math.floor(alphaIndices.length / 2)),
            );

            for (let i = 0; i < targetCount; i++) {
                const pickIdx = Math.min(
                    candidatePool.length - 1,
                    Math.floor((i * candidatePool.length) / targetCount),
                );
                blankSet.add(candidatePool[pickIdx]);
            }
        }

        const slots: SpellingSlot[] = chars.map((ch, idx) => ({
            index: idx,
            char: ch,
            isBlank: blankSet.has(idx),
        }));

        const blanks = slots.filter((slot) => slot.isBlank);
        const orderedTiles: SpellingTile[] = blanks.map((slot, idx) => ({
            tileId: idx,
            slotIndex: slot.index,
            char: slot.char.toLowerCase(),
        }));

        const sortedTiles = [...orderedTiles].sort((a, b) => {
            const scoreA = (a.char.charCodeAt(0) * 31 + a.slotIndex * 17) % 97;
            const scoreB = (b.char.charCodeAt(0) * 31 + b.slotIndex * 17) % 97;
            return scoreA - scoreB || a.slotIndex - b.slotIndex;
        });

        // Ensure scrambled order differs from answer order whenever possible
        if (
            sortedTiles.length >= 2 &&
            sortedTiles.every((tile, i) => tile.char === orderedTiles[i].char)
        ) {
            const first = sortedTiles.shift();
            if (first) {
                sortedTiles.push(first);
            }
        }

        return {
            slots,
            tiles: sortedTiles,
            blankCount: blanks.length,
        };
    });

    // Maps slotIndex -> tileId
    let filledSlots = $state<Record<number, number>>({});

    const usedTileIds = $derived(new Set(Object.values(filledSlots)));

    const tileById = $derived.by(() => {
        const map = new Map<number, SpellingTile>();
        for (const tile of spellingPuzzle.tiles) {
            map.set(tile.tileId, tile);
        }
        return map;
    });

    const isSpellingComplete = $derived(
        spellingPuzzle.blankCount > 0 &&
            Object.keys(filledSlots).length === spellingPuzzle.blankCount,
    );

    const isSpellingCorrect = $derived.by(() => {
        if (!isSpellingComplete) {
            return false;
        }
        return spellingPuzzle.slots.every((slot) => {
            if (!slot.isBlank) {
                return true;
            }
            const tileId = filledSlots[slot.index];
            const tile =
                tileId !== undefined ? tileById.get(tileId) : undefined;
            return tile?.char === slot.char.toLowerCase();
        });
    });

    function handleTileSelect(tile: SpellingTile): void {
        if (usedTileIds.has(tile.tileId) || isSpellingComplete) {
            return;
        }

        const nextBlank = spellingPuzzle.slots.find(
            (slot) => slot.isBlank && filledSlots[slot.index] === undefined,
        );
        if (!nextBlank) {
            return;
        }

        const nextState = {
            ...filledSlots,
            [nextBlank.index]: tile.tileId,
        };
        filledSlots = nextState;

        if (Object.keys(nextState).length === spellingPuzzle.blankCount) {
            const solved = spellingPuzzle.slots.every((slot) => {
                if (!slot.isBlank) {
                    return true;
                }
                const pickedId = nextState[slot.index];
                const pickedTile =
                    pickedId !== undefined ? tileById.get(pickedId) : undefined;
                return pickedTile?.char === slot.char.toLowerCase();
            });
            if (solved && activeVocab.hasAudio) {
                playWordAudio();
            }
        }
    }

    function handleSlotClear(slotIndex: number): void {
        if (filledSlots[slotIndex] === undefined || isSpellingCorrect) {
            return;
        }
        const nextState = { ...filledSlots };
        delete nextState[slotIndex];
        filledSlots = nextState;
    }

    function resetSpelling(): void {
        filledSlots = {};
    }

    // Safe sentence word emphasis without @html
    interface SentenceChunk {
        text: string;
        highlight: boolean;
    }

    const highlightedSentence = $derived.by((): SentenceChunk[] => {
        const sentence = activeVocab.example_sentence;
        const word = activeVocab.word.trim();
        if (!sentence || !word) {
            return [{ text: sentence, highlight: false }];
        }

        const escaped = word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escaped}[a-zA-Z]*)`, 'i');
        const match = regex.exec(sentence);
        if (!match || match.index === undefined) {
            return [{ text: sentence, highlight: false }];
        }

        const start = match.index;
        const end = start + match[0].length;
        const chunks: SentenceChunk[] = [];
        if (start > 0) {
            chunks.push({ text: sentence.slice(0, start), highlight: false });
        }
        chunks.push({ text: sentence.slice(start, end), highlight: true });
        if (end < sentence.length) {
            chunks.push({ text: sentence.slice(end), highlight: false });
        }
        return chunks;
    });

    // Band 2: SRS Interactive Curve & 6 Stages
    // Plot coordinate system: x in [56..660], y in [28..270] (0% -> y=270, 100% -> y=28)
    interface SrsStageNode {
        stage: number;
        cx: number;
        cy: number;
        dayLabel: string;
    }

    const srsNodes: SrsStageNode[] = [
        { stage: 1, cx: 120, cy: 43, dayLabel: '12h' },
        { stage: 2, cx: 196, cy: 40, dayLabel: '1d' },
        { stage: 3, cx: 288, cy: 38, dayLabel: '2d' },
        { stage: 4, cx: 402, cy: 38, dayLabel: '4d' },
        { stage: 5, cx: 526, cy: 35, dayLabel: '7d' },
        { stage: 6, cx: 644, cy: 33, dayLabel: '15d+' },
    ];

    let activeSrsStage = $state<number>(4);

    const activeSrsNode = $derived(
        srsNodes.find((n) => n.stage === activeSrsStage) ?? srsNodes[3],
    );

    // Sawtooth SRS retention curve: slight dip right before each scheduled review, then snapped back up
    const srsCurvePath =
        'M 56 28 Q 92 42 120 74 L 120 43 Q 160 56 196 80 L 196 40 Q 245 50 288 72 L 288 38 Q 348 48 402 68 L 402 38 Q 468 46 526 60 L 526 35 Q 588 42 644 48 L 644 33 L 664 33';

    // Ebbinghaus cramming decay curve: 100% (y=28) dropping steeply to ~14% (y=236)
    const crammingCurvePath =
        'M 56 28 C 102 145, 165 202, 260 222 C 370 234, 510 236, 664 238';

    // Band 3: Editorial Features & Interactive Quiz Anatomy
    type PreviewMode = 'word' | 'audio' | 'cloze';
    let previewMode = $state<PreviewMode>('word');
    let selectedOptionIndex = $state<number | null>(null);

    const previewAnswers: Record<PreviewMode, number> = {
        word: 0,
        audio: 1,
        cloze: 2,
    };

    const activePreviewSample = $derived.by(() => {
        const correctIndex = previewAnswers[previewMode];
        return {
            prompt: t(
                `ui.welcome.features.preview_samples.${previewMode}.prompt`,
            ),
            pos: t(`ui.welcome.features.preview_samples.${previewMode}.pos`),
            options: [
                t(
                    `ui.welcome.features.preview_samples.${previewMode}.option_0`,
                ),
                t(
                    `ui.welcome.features.preview_samples.${previewMode}.option_1`,
                ),
                t(
                    `ui.welcome.features.preview_samples.${previewMode}.option_2`,
                ),
                t(
                    `ui.welcome.features.preview_samples.${previewMode}.option_3`,
                ),
            ],
            correctIndex,
        };
    });

    function switchPreviewMode(mode: PreviewMode): void {
        previewMode = mode;
        selectedOptionIndex = null;
    }

    const featureRows = [
        {
            icon: Brain,
            titleKey: 'ui.welcome.features.srs_title',
            descKey: 'ui.welcome.features.srs_desc',
        },
        {
            icon: Cloud,
            titleKey: 'ui.welcome.features.guest_title',
            descKey: 'ui.welcome.features.guest_desc',
        },
        {
            icon: Layers,
            titleKey: 'ui.welcome.features.quiz_title',
            descKey: 'ui.welcome.features.quiz_desc',
        },
        {
            icon: Smartphone,
            titleKey: 'ui.welcome.features.mobile_title',
            descKey: 'ui.welcome.features.mobile_desc',
        },
    ];

    // Band 4: Level helper
    function parseLevelInfo(item: LevelData) {
        if (item.description?.includes('：')) {
            const [title, ...rest] = item.description.split('：');
            return {
                title: title.trim(),
                description: rest.join('：').trim(),
            };
        }
        return {
            title: item.name,
            description: item.description,
        };
    }

    const primaryHeroHref = $derived(
        auth.user ? dashboard.url() : levelsIndex.url(),
    );
    const primaryCtaHref = $derived(
        levels.length > 0 ? levelsShow.url(levels[0].id) : levelsIndex.url(),
    );
</script>

<AppHead title={t('ui.welcome.hero.title')} />

<div class="selection:bg-blue-600 selection:text-white">
    <!-- Band 1: Hero & Interactive Study Workbench (bleeds to y=0 behind transparent header) -->
    <section class="relative overflow-hidden pt-28 pb-20 sm:pt-32 sm:pb-28">
        <!-- Subtle architectural sky-cobalt aura and hairline paper grid -->
        <div
            aria-hidden="true"
            class="hero-paper-grid pointer-events-none absolute inset-0"
        ></div>
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 top-0 h-[420px] bg-radial-[at_50%_0%] from-blue-500/12 via-blue-500/4 to-transparent dark:from-blue-400/15 dark:via-blue-500/5"
        ></div>

        <div
            class="relative mx-auto max-w-6xl px-4 text-center sm:px-6 lg:px-8"
        >
            <h1
                class="text-foreground mx-auto text-[clamp(2.15rem,4.6vw,4rem)] leading-[1.12] font-extrabold tracking-[-0.03em]"
            >
                {t('ui.welcome.hero.title')}
            </h1>

            <p
                class="text-muted-foreground mx-auto mt-6 max-w-2xl text-base leading-relaxed text-balance"
            >
                {t('ui.welcome.hero.subtitle')}
            </p>

            <div class="mt-8 flex justify-center">
                <Button
                    asChild
                    size="lg"
                    class="h-12 bg-blue-600 px-7 text-base font-semibold text-white shadow-sm transition-colors duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 motion-reduce:transition-none"
                >
                    {#snippet children(props)}
                        <Link href={primaryHeroHref} class={props.class}>
                            <span>
                                {auth.user
                                    ? t('ui.welcome.hero.dashboard')
                                    : t('ui.welcome.hero.start_learning')}
                            </span>
                            <ArrowRight class="size-4.5" />
                        </Link>
                    {/snippet}
                </Button>
            </div>
        </div>

        <!-- Wide Interactive Study Workbench -->
        <div
            class="relative mx-auto mt-12 max-w-4xl px-4 sm:mt-14 sm:px-6 lg:px-8"
        >
            <section
                aria-label={t('ui.welcome.hero.workbench_label')}
                class="border-border bg-card/95 rounded-2xl border shadow-sm backdrop-blur-xs"
            >
                <!-- Workbench Control Strip -->
                <div
                    class="border-border flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3.5 sm:px-8"
                >
                    <div
                        role="tablist"
                        aria-label={t('ui.welcome.hero.workbench_label')}
                        class="bg-muted/70 inline-flex rounded-lg p-1"
                    >
                        {#each ['card', 'recall', 'spelling'] as const as mode (mode)}
                            {@const isActive = workbenchMode === mode}
                            <button
                                type="button"
                                role="tab"
                                aria-selected={isActive}
                                onclick={() => switchWorkbenchMode(mode)}
                                class={{
                                    'cursor-pointer rounded-md px-3.5 py-1.5 text-sm font-semibold transition-[background-color,color,box-shadow] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none': true,
                                    'bg-background text-foreground shadow-2xs':
                                        isActive,
                                    'text-muted-foreground hover:text-foreground':
                                        !isActive,
                                }}
                            >
                                {t(`ui.welcome.hero.modes.${mode}`)}
                            </button>
                        {/each}
                    </div>

                    {#if activeVocab.hasAudio}
                        <button
                            type="button"
                            onclick={playWordAudio}
                            aria-label={t('ui.welcome.hero.play_word')}
                            title={t('ui.welcome.hero.play_word')}
                            class={{
                                'inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition-[background-color,border-color,color] duration-150 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none': true,
                                'border-blue-600 bg-blue-600 text-white':
                                    audioPlayer.isPlaying('hero-word'),
                                'border-border bg-background text-foreground hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-400':
                                    !audioPlayer.isPlaying('hero-word'),
                            }}
                        >
                            <Volume2 class="size-4" />
                            <span>{t('ui.welcome.hero.play_word')}</span>
                        </button>
                    {/if}
                </div>

                <!-- Workbench Active Stage -->
                <div class="px-5 py-7 sm:px-8 sm:py-9">
                    {#if workbenchMode === 'card'}
                        <div class="space-y-6">
                            <div
                                class="flex flex-wrap items-baseline justify-between gap-4"
                            >
                                <div
                                    class="flex flex-wrap items-baseline gap-3"
                                >
                                    <span
                                        class="text-foreground text-3xl font-extrabold tracking-tight sm:text-4xl"
                                    >
                                        {activeVocab.word}
                                    </span>
                                    {#if activeVocab.part_of_speech}
                                        <span
                                            class="text-muted-foreground text-sm font-semibold italic"
                                        >
                                            {activeVocab.part_of_speech}
                                        </span>
                                    {/if}
                                    {#if activeVocab.pronunciation}
                                        <span
                                            class="text-muted-foreground text-sm tabular-nums"
                                        >
                                            {activeVocab.pronunciation}
                                        </span>
                                    {/if}
                                </div>
                            </div>

                            <p
                                class="text-foreground text-base leading-relaxed font-semibold"
                            >
                                {activeVocab.definition}
                            </p>

                            {#if activeVocab.example_sentence}
                                <div
                                    class="border-border/80 flex items-start justify-between gap-4 border-t pt-5"
                                >
                                    <div class="space-y-1.5">
                                        <p
                                            class="text-foreground text-base leading-relaxed"
                                        >
                                            {#each highlightedSentence as chunk, idx (idx)}
                                                {#if chunk.highlight}
                                                    <span
                                                        class="font-semibold text-blue-600 dark:text-blue-400"
                                                        >{chunk.text}</span
                                                    >
                                                {:else}
                                                    {chunk.text}
                                                {/if}
                                            {/each}
                                        </p>
                                        {#if activeVocab.example_translation}
                                            <p
                                                class="text-muted-foreground text-sm leading-relaxed"
                                            >
                                                {activeVocab.example_translation}
                                            </p>
                                        {/if}
                                    </div>

                                    {#if activeVocab.hasAudio}
                                        <button
                                            type="button"
                                            onclick={playSentenceAudio}
                                            aria-label={t(
                                                'ui.welcome.hero.play_sentence',
                                            )}
                                            title={t(
                                                'ui.welcome.hero.play_sentence',
                                            )}
                                            class={{
                                                'flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full border transition-[background-color,border-color,color,transform] duration-150 active:scale-95 motion-reduce:transition-none': true,
                                                'border-blue-600 bg-blue-600 text-white':
                                                    audioPlayer.isPlaying(
                                                        'hero-sentence',
                                                    ),
                                                'border-border bg-background text-muted-foreground hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-400':
                                                    !audioPlayer.isPlaying(
                                                        'hero-sentence',
                                                    ),
                                            }}
                                        >
                                            <Volume2 class="size-4.5" />
                                        </button>
                                    {/if}
                                </div>
                            {/if}
                        </div>
                    {:else if workbenchMode === 'recall'}
                        <div class="space-y-6">
                            <div
                                class="flex flex-wrap items-baseline justify-between gap-4"
                            >
                                <div
                                    class="flex flex-wrap items-baseline gap-3"
                                >
                                    <span
                                        class="text-foreground text-3xl font-extrabold tracking-tight sm:text-4xl"
                                    >
                                        {activeVocab.word}
                                    </span>
                                    {#if activeVocab.pronunciation}
                                        <span
                                            class="text-muted-foreground text-sm tabular-nums"
                                        >
                                            {activeVocab.pronunciation}
                                        </span>
                                    {/if}
                                </div>

                                <button
                                    type="button"
                                    onclick={() => {
                                        recallRevealed = !recallRevealed;
                                    }}
                                    class="text-foreground inline-flex cursor-pointer items-center gap-2 text-sm font-semibold transition-colors duration-150 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none dark:hover:text-blue-400"
                                >
                                    {#if recallRevealed}
                                        <EyeOff class="size-4" />
                                        <span
                                            >{t(
                                                'ui.welcome.hero.recall_hide',
                                            )}</span
                                        >
                                    {:else}
                                        <Eye class="size-4" />
                                        <span
                                            >{t(
                                                'ui.welcome.hero.recall_reveal',
                                            )}</span
                                        >
                                    {/if}
                                </button>
                            </div>

                            {#if recallRevealed}
                                <div class="space-y-4">
                                    <p
                                        class="text-foreground text-base leading-relaxed font-semibold"
                                    >
                                        {#if activeVocab.part_of_speech}
                                            <span
                                                class="text-muted-foreground mr-2 text-sm font-semibold italic"
                                            >
                                                {activeVocab.part_of_speech}
                                            </span>
                                        {/if}
                                        {activeVocab.definition}
                                    </p>

                                    {#if activeVocab.example_sentence}
                                        <div
                                            class="border-border/80 space-y-1 border-t pt-4"
                                        >
                                            <p
                                                class="text-foreground text-base leading-relaxed"
                                            >
                                                {activeVocab.example_sentence}
                                            </p>
                                            {#if activeVocab.example_translation}
                                                <p
                                                    class="text-muted-foreground text-sm leading-relaxed"
                                                >
                                                    {activeVocab.example_translation}
                                                </p>
                                            {/if}
                                        </div>
                                    {/if}

                                    <div
                                        class="border-border/80 flex flex-wrap items-center justify-between gap-4 border-t pt-4"
                                    >
                                        <div
                                            role="radiogroup"
                                            aria-label={t(
                                                'ui.welcome.hero.modes.recall',
                                            )}
                                            class="bg-muted/70 inline-flex rounded-lg p-1"
                                        >
                                            <button
                                                type="button"
                                                role="radio"
                                                aria-checked={recallChoice ===
                                                    'known'}
                                                onclick={() =>
                                                    (recallChoice = 'known')}
                                                class={{
                                                    'cursor-pointer rounded-md px-3.5 py-1.5 text-sm font-semibold transition-[background-color,color] duration-150 motion-reduce:transition-none': true,
                                                    'bg-emerald-600 text-white':
                                                        recallChoice ===
                                                        'known',
                                                    'text-muted-foreground hover:text-foreground':
                                                        recallChoice !==
                                                        'known',
                                                }}
                                            >
                                                {t(
                                                    'ui.welcome.hero.recall_known',
                                                )}
                                            </button>
                                            <button
                                                type="button"
                                                role="radio"
                                                aria-checked={recallChoice ===
                                                    'review'}
                                                onclick={() =>
                                                    (recallChoice = 'review')}
                                                class={{
                                                    'cursor-pointer rounded-md px-3.5 py-1.5 text-sm font-semibold transition-[background-color,color] duration-150 motion-reduce:transition-none': true,
                                                    'bg-blue-600 text-white':
                                                        recallChoice ===
                                                        'review',
                                                    'text-muted-foreground hover:text-foreground':
                                                        recallChoice !==
                                                        'review',
                                                }}
                                            >
                                                {t(
                                                    'ui.welcome.hero.recall_review',
                                                )}
                                            </button>
                                        </div>

                                        {#if recallChoice === 'known'}
                                            <p
                                                class="flex items-center gap-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-400"
                                            >
                                                <Check class="size-4" />
                                                <span>
                                                    {t(
                                                        'ui.welcome.hero.recall_known_feedback',
                                                    )}
                                                </span>
                                            </p>
                                        {:else if recallChoice === 'review'}
                                            <p
                                                class="text-sm font-medium text-blue-700 dark:text-blue-400"
                                            >
                                                {t(
                                                    'ui.welcome.hero.recall_review_feedback',
                                                )}
                                            </p>
                                        {/if}
                                    </div>
                                </div>
                            {:else}
                                <button
                                    type="button"
                                    onclick={() => (recallRevealed = true)}
                                    class="border-border bg-muted/40 text-muted-foreground hover:text-foreground flex w-full cursor-pointer items-center justify-center rounded-xl border border-dashed py-8 text-base font-medium transition-[border-color,color] duration-150 hover:border-blue-400/70 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none"
                                >
                                    {t('ui.welcome.hero.recall_hint')}
                                </button>
                            {/if}
                        </div>
                    {:else}
                        <div class="space-y-6">
                            <div
                                class="flex flex-wrap items-baseline justify-between gap-4"
                            >
                                <div>
                                    <p
                                        class="text-foreground text-base font-semibold"
                                    >
                                        {activeVocab.definition}
                                    </p>
                                    <p
                                        class="text-muted-foreground mt-1 text-sm"
                                    >
                                        {t('ui.welcome.hero.spelling_prompt')}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onclick={resetSpelling}
                                    class="text-muted-foreground hover:text-foreground inline-flex cursor-pointer items-center gap-1.5 text-sm font-medium transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none"
                                >
                                    <RotateCcw class="size-4" />
                                    <span
                                        >{t(
                                            'ui.welcome.hero.spelling_reset',
                                        )}</span
                                    >
                                </button>
                            </div>

                            <!-- Word Slots -->
                            <div
                                aria-label={t(
                                    'ui.welcome.hero.spelling_slots_label',
                                )}
                                class="flex flex-wrap items-center gap-1.5 sm:gap-2"
                            >
                                {#each spellingPuzzle.slots as slot (slot.index)}
                                    {#if slot.isBlank}
                                        {@const assignedTileId =
                                            filledSlots[slot.index]}
                                        {@const assignedTile =
                                            assignedTileId !== undefined
                                                ? tileById.get(assignedTileId)
                                                : undefined}
                                        <button
                                            type="button"
                                            onclick={() =>
                                                handleSlotClear(slot.index)}
                                            disabled={assignedTile ===
                                                undefined || isSpellingCorrect}
                                            class={{
                                                'flex h-12 min-w-9 items-center justify-center rounded-lg border-b-2 px-2 text-3xl font-extrabold tracking-tight transition-[border-color,background-color,color] duration-150 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none sm:text-4xl': true,
                                                'border-emerald-600 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300':
                                                    isSpellingCorrect,
                                                'cursor-pointer border-red-500 bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300':
                                                    isSpellingComplete &&
                                                    !isSpellingCorrect,
                                                'cursor-pointer border-blue-600 bg-blue-50/70 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300':
                                                    !isSpellingComplete &&
                                                    assignedTile !== undefined,
                                                'border-border bg-muted/55 border border-b-2 border-b-blue-300/80 text-transparent dark:border-b-blue-700/70':
                                                    assignedTile === undefined,
                                            }}
                                        >
                                            {assignedTile?.char ?? '_'}
                                        </button>
                                    {:else}
                                        <span
                                            class="text-foreground flex h-12 items-center px-0.5 text-3xl font-extrabold tracking-tight sm:text-4xl"
                                        >
                                            {slot.char}
                                        </span>
                                    {/if}
                                {/each}
                            </div>

                            <!-- Tactile Letter Tiles -->
                            <div
                                class="border-border/80 flex flex-wrap items-center justify-between gap-4 border-t pt-5"
                            >
                                <div
                                    aria-label={t(
                                        'ui.welcome.hero.spelling_tiles_label',
                                    )}
                                    class="flex flex-wrap items-center gap-2.5"
                                >
                                    {#each spellingPuzzle.tiles as tile (tile.tileId)}
                                        {@const used = usedTileIds.has(
                                            tile.tileId,
                                        )}
                                        <button
                                            type="button"
                                            disabled={used ||
                                                isSpellingComplete}
                                            onclick={() =>
                                                handleTileSelect(tile)}
                                            class={{
                                                'flex size-11 items-center justify-center rounded-xl border text-base font-bold transition-[transform,background-color,border-color,opacity,box-shadow] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none': true,
                                                'border-border/50 bg-muted/30 text-muted-foreground/30 pointer-events-none translate-y-0.5 border-dashed shadow-none':
                                                    used,
                                                'cursor-pointer border-blue-400/80 bg-blue-50/90 text-blue-700 shadow-[0_3px_0_0_rgba(37,99,235,0.28)] hover:-translate-y-0.5 hover:border-blue-500 active:translate-y-[3px] active:shadow-none dark:border-blue-500/70 dark:bg-blue-950/50 dark:text-blue-300 dark:shadow-[0_3px_0_0_rgba(59,130,246,0.34)]':
                                                    !used &&
                                                    !isSpellingComplete,
                                                'border-border bg-muted/40 text-muted-foreground opacity-50 shadow-none':
                                                    !used && isSpellingComplete,
                                            }}
                                        >
                                            {tile.char}
                                        </button>
                                    {/each}
                                </div>

                                {#if isSpellingCorrect}
                                    <p
                                        class="flex items-center gap-1.5 text-sm font-semibold text-emerald-700 dark:text-emerald-400"
                                    >
                                        <Check class="size-4" />
                                        <span>
                                            {t(
                                                'ui.welcome.hero.spelling_solved',
                                            )}
                                        </span>
                                    </p>
                                {:else if isSpellingComplete}
                                    <p
                                        class="text-sm font-medium text-red-600 dark:text-red-400"
                                    >
                                        {t('ui.welcome.hero.spelling_wrong')}
                                    </p>
                                {/if}
                            </div>
                        </div>
                    {/if}
                </div>
            </section>
        </div>
    </section>

    <!-- Band 2: Interactive Ebbinghaus vs. Vomorize 6-Stage SRS Curve -->
    <section
        id="srs-science"
        class="border-border/80 bg-muted/35 border-y py-20 sm:py-28"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                class="grid grid-cols-1 items-start gap-12 lg:grid-cols-12 lg:gap-16"
            >
                <!-- Left Column: Heading, Comparison Numbers, 6-Stage Interactive Selector -->
                <div class="lg:col-span-5">
                    <h2
                        class="text-foreground text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl"
                    >
                        {t('ui.welcome.srs.title')}
                    </h2>
                    <p
                        class="text-muted-foreground mt-4 max-w-[54ch] text-base leading-relaxed"
                    >
                        {t('ui.welcome.srs.subtitle')}
                    </p>

                    <!-- Clean Typographic Legend & Outcome Comparison -->
                    <div
                        class="border-border mt-8 grid grid-cols-2 gap-6 border-t pt-6"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    aria-hidden="true"
                                    class="inline-block h-0.5 w-5 bg-emerald-600 dark:bg-emerald-400"
                                ></span>
                                <span
                                    class="text-foreground text-sm font-semibold"
                                >
                                    {t('ui.welcome.srs.legend_srs')}
                                </span>
                            </div>
                            <p
                                class="mt-1.5 text-sm font-medium text-emerald-700 tabular-nums dark:text-emerald-400"
                            >
                                {t('ui.welcome.srs.srs_summary')}
                            </p>
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    aria-hidden="true"
                                    class="inline-block h-0.5 w-5 border-t-2 border-dashed border-red-500"
                                ></span>
                                <span
                                    class="text-foreground text-sm font-semibold"
                                >
                                    {t('ui.welcome.srs.legend_cramming')}
                                </span>
                            </div>
                            <p
                                class="mt-1.5 text-sm font-medium text-red-600 tabular-nums dark:text-red-400"
                            >
                                {t('ui.welcome.srs.cramming_summary')}
                            </p>
                        </div>
                    </div>

                    <!-- Interactive 6-Stage Rail -->
                    <div
                        role="tablist"
                        aria-label={t('ui.welcome.srs.title')}
                        class="border-border divide-border mt-8 divide-y border-y"
                    >
                        {#each srsNodes as node (node.stage)}
                            {@const isActive = activeSrsStage === node.stage}
                            <button
                                type="button"
                                role="tab"
                                aria-selected={isActive}
                                onclick={() => (activeSrsStage = node.stage)}
                                class={{
                                    'flex w-full cursor-pointer items-center justify-between gap-4 py-3.5 text-left transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none': true,
                                    'border-l-2 border-l-blue-600 pl-3.5 dark:border-l-blue-400':
                                        isActive,
                                    'hover:bg-muted/50 border-l-2 border-l-transparent pl-3.5':
                                        !isActive,
                                }}
                            >
                                <div class="flex items-baseline gap-3">
                                    <span
                                        class={{
                                            'text-sm font-semibold tabular-nums': true,
                                            'text-blue-600 dark:text-blue-400':
                                                isActive,
                                            'text-muted-foreground': !isActive,
                                        }}
                                    >
                                        {t(
                                            `ui.welcome.srs.stages.${node.stage}.time`,
                                        )}
                                    </span>
                                    <span
                                        class={{
                                            'text-base font-bold': true,
                                            'text-foreground': isActive,
                                            'text-foreground/80': !isActive,
                                        }}
                                    >
                                        {t(
                                            `ui.welcome.srs.stages.${node.stage}.title`,
                                        )}
                                    </span>
                                </div>

                                <span
                                    class={{
                                        'text-sm font-semibold tabular-nums': true,
                                        'text-emerald-700 dark:text-emerald-400':
                                            isActive,
                                        'text-muted-foreground': !isActive,
                                    }}
                                >
                                    {t(
                                        `ui.welcome.srs.stages.${node.stage}.retention`,
                                    )}
                                </span>
                            </button>
                        {/each}
                    </div>
                </div>

                <!-- Right Column: Unboxed Precision SVG Forgetting Curve -->
                <div class="lg:col-span-7">
                    <div class="w-full">
                        <svg
                            viewBox="0 0 680 315"
                            role="img"
                            aria-label={t('ui.welcome.srs.chart_aria')}
                            class="h-auto w-full overflow-visible select-none"
                        >
                            <!-- Horizontal Reference Gridlines -->
                            <line
                                x1="56"
                                y1="28"
                                x2="664"
                                y2="28"
                                class="stroke-border"
                                stroke-width="1"
                            />
                            <line
                                x1="56"
                                y1="108"
                                x2="664"
                                y2="108"
                                class="stroke-border/60"
                                stroke-width="1"
                                stroke-dasharray="3 4"
                            />
                            <line
                                x1="56"
                                y1="188"
                                x2="664"
                                y2="188"
                                class="stroke-border/60"
                                stroke-width="1"
                                stroke-dasharray="3 4"
                            />
                            <line
                                x1="56"
                                y1="270"
                                x2="664"
                                y2="270"
                                class="stroke-border"
                                stroke-width="1.5"
                            />
                            <line
                                x1="56"
                                y1="28"
                                x2="56"
                                y2="270"
                                class="stroke-border"
                                stroke-width="1.5"
                            />

                            <!-- Y-axis Percentages (>= 14px) -->
                            <text
                                x="44"
                                y="33"
                                text-anchor="end"
                                class="fill-muted-foreground text-[14px] font-medium tabular-nums"
                            >
                                100%
                            </text>
                            <text
                                x="44"
                                y="113"
                                text-anchor="end"
                                class="fill-muted-foreground text-[14px] font-medium tabular-nums"
                            >
                                65%
                            </text>
                            <text
                                x="44"
                                y="193"
                                text-anchor="end"
                                class="fill-muted-foreground text-[14px] font-medium tabular-nums"
                            >
                                35%
                            </text>
                            <text
                                x="44"
                                y="274"
                                text-anchor="end"
                                class="fill-muted-foreground text-[14px] font-medium tabular-nums"
                            >
                                0%
                            </text>

                            <!-- Active Stage Vertical Guide -->
                            <line
                                x1={activeSrsNode.cx}
                                y1={activeSrsNode.cy}
                                x2={activeSrsNode.cx}
                                y2="270"
                                class="stroke-blue-600/40 dark:stroke-blue-400/40"
                                stroke-width="1.5"
                                stroke-dasharray="4 4"
                            />

                            <!-- Ebbinghaus Cramming Decay Curve -->
                            <path
                                d={crammingCurvePath}
                                fill="none"
                                class="stroke-red-500/80 dark:stroke-red-400/80"
                                stroke-width="2.5"
                                stroke-dasharray="6 5"
                                stroke-linecap="round"
                            />

                            <!-- Vomorize Sawtooth SRS Curve -->
                            <path
                                d={srsCurvePath}
                                fill="none"
                                class="stroke-emerald-600 dark:stroke-emerald-400"
                                stroke-width="3"
                                stroke-linejoin="round"
                                stroke-linecap="round"
                            />

                            <!-- Interactive Nodes at the 6 SRS Review Points -->
                            {#each srsNodes as node (node.stage)}
                                {@const isActive =
                                    activeSrsStage === node.stage}
                                <g
                                    class="cursor-pointer"
                                    onclick={() =>
                                        (activeSrsStage = node.stage)}
                                    onkeydown={(e) => {
                                        if (
                                            e.key === 'Enter' ||
                                            e.key === ' '
                                        ) {
                                            e.preventDefault();
                                            activeSrsStage = node.stage;
                                        }
                                    }}
                                    role="button"
                                    tabindex="0"
                                    aria-label={t(
                                        'ui.welcome.srs.stage_label',
                                        {
                                            stage: node.stage,
                                        },
                                    )}
                                >
                                    <circle
                                        cx={node.cx}
                                        cy={node.cy}
                                        r="16"
                                        fill="transparent"
                                    />
                                    {#if isActive}
                                        <circle
                                            cx={node.cx}
                                            cy={node.cy}
                                            r="10"
                                            class="fill-blue-600/15 dark:fill-blue-400/20"
                                        />
                                    {/if}
                                    <circle
                                        cx={node.cx}
                                        cy={node.cy}
                                        r={isActive ? 6 : 4.5}
                                        class={{
                                            'stroke-background stroke-2': true,
                                            'fill-blue-600 dark:fill-blue-400':
                                                isActive,
                                            'fill-emerald-600 dark:fill-emerald-400':
                                                !isActive,
                                        }}
                                    />
                                    <text
                                        x={node.cx}
                                        y="296"
                                        text-anchor="middle"
                                        class={{
                                            'text-[14px] font-semibold tabular-nums': true,
                                            'fill-blue-600 dark:fill-blue-400':
                                                isActive,
                                            'fill-muted-foreground': !isActive,
                                        }}
                                    >
                                        {node.dayLabel}
                                    </text>
                                </g>
                            {/each}
                        </svg>

                        <!-- Active Stage Architectural Readout -->
                        <div
                            class="border-border mt-6 flex flex-col justify-between gap-4 border-t pt-6 sm:flex-row sm:items-baseline"
                        >
                            <div class="space-y-1">
                                <h3 class="text-foreground text-base font-bold">
                                    {t('ui.welcome.srs.stage_label', {
                                        stage: activeSrsStage,
                                    })}
                                    {t(
                                        `ui.welcome.srs.stages.${activeSrsStage}.title`,
                                    )}
                                </h3>
                                <p
                                    class="text-muted-foreground max-w-[54ch] text-base leading-relaxed"
                                >
                                    {t(
                                        `ui.welcome.srs.stages.${activeSrsStage}.desc`,
                                    )}
                                </p>
                            </div>

                            <div class="shrink-0 sm:text-right">
                                <span
                                    class="text-muted-foreground block text-sm"
                                >
                                    {t('ui.welcome.srs.retention_label')}
                                </span>
                                <span
                                    class="text-3xl font-extrabold text-emerald-600 tabular-nums dark:text-emerald-400"
                                >
                                    {t(
                                        `ui.welcome.srs.stages.${activeSrsStage}.retention`,
                                    )}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Band 3: Editorial Feature Craft & Quiz Anatomy -->
    <section id="features" class="py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                class="grid grid-cols-1 items-start gap-12 lg:grid-cols-12 lg:gap-16"
            >
                <!-- Left Sticky Column: Heading + Interactive Quiz Anatomy Mini-Preview -->
                <div class="lg:sticky lg:top-24 lg:col-span-5">
                    <h2
                        class="text-foreground text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl"
                    >
                        {t('ui.welcome.features.title')}
                    </h2>
                    <p
                        class="text-muted-foreground mt-4 max-w-[48ch] text-base leading-relaxed"
                    >
                        {t('ui.welcome.features.subtitle')}
                    </p>

                    <!-- Inline Quiz Anatomy Preview (hairline framed, no nested card box) -->
                    <div class="border-border mt-8 border-t pt-7">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <h3 class="text-foreground text-base font-bold">
                                {t('ui.welcome.features.preview_label')}
                            </h3>

                            <div
                                role="tablist"
                                aria-label={t(
                                    'ui.welcome.features.preview_label',
                                )}
                                class="bg-muted/70 inline-flex rounded-lg p-1"
                            >
                                {#each ['word', 'audio', 'cloze'] as const as mode (mode)}
                                    {@const isActive = previewMode === mode}
                                    <button
                                        type="button"
                                        role="tab"
                                        aria-selected={isActive}
                                        onclick={() => switchPreviewMode(mode)}
                                        class={{
                                            'cursor-pointer rounded-md px-2.5 py-1 text-sm font-medium transition-[background-color,color] duration-150 motion-reduce:transition-none': true,
                                            'bg-background text-foreground shadow-2xs':
                                                isActive,
                                            'text-muted-foreground hover:text-foreground':
                                                !isActive,
                                        }}
                                    >
                                        {t(
                                            `ui.welcome.features.preview_modes.${mode}`,
                                        )}
                                    </button>
                                {/each}
                            </div>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div
                                class="flex flex-wrap items-baseline justify-between gap-2"
                            >
                                <p class="text-foreground text-base font-bold">
                                    {activePreviewSample.prompt}
                                </p>
                                <span
                                    class={{
                                        'text-sm font-medium': true,
                                        'text-blue-600 dark:text-blue-400':
                                            selectedOptionIndex !== null,
                                        'text-muted-foreground':
                                            selectedOptionIndex === null,
                                    }}
                                >
                                    {selectedOptionIndex === null
                                        ? t(
                                              'ui.welcome.features.preview_pos_hidden',
                                          )
                                        : t(
                                              'ui.welcome.features.preview_pos_revealed',
                                              {
                                                  pos: activePreviewSample.pos,
                                              },
                                          )}
                                </span>
                            </div>

                            <div
                                class="divide-border border-border divide-y border-y"
                            >
                                {#each activePreviewSample.options as option, idx (idx)}
                                    {@const isPicked =
                                        selectedOptionIndex === idx}
                                    {@const isRight =
                                        idx ===
                                        activePreviewSample.correctIndex}
                                    {@const isAnswered =
                                        selectedOptionIndex !== null}
                                    <button
                                        type="button"
                                        disabled={isAnswered}
                                        onclick={() =>
                                            (selectedOptionIndex = idx)}
                                        class={{
                                            'flex w-full items-center justify-between gap-3 rounded-md px-2.5 py-3 text-left text-base transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none': true,
                                            'hover:bg-muted/40 cursor-pointer hover:text-blue-600 dark:hover:text-blue-400':
                                                !isAnswered,
                                            'font-semibold text-emerald-700 dark:text-emerald-400':
                                                isAnswered && isRight,
                                            'font-semibold text-red-600 dark:text-red-400':
                                                isAnswered &&
                                                isPicked &&
                                                !isRight,
                                            'text-muted-foreground':
                                                isAnswered &&
                                                !isRight &&
                                                !isPicked,
                                        }}
                                    >
                                        <span>{option}</span>
                                        {#if isAnswered && isRight}
                                            <Check
                                                class="size-4 text-emerald-600 dark:text-emerald-400"
                                            />
                                        {/if}
                                    </button>
                                {/each}
                            </div>

                            <div
                                class="flex items-center justify-between gap-3 text-sm"
                            >
                                {#if selectedOptionIndex === null}
                                    <span class="text-muted-foreground">
                                        {t(
                                            'ui.welcome.features.preview_prompt_hint',
                                        )}
                                    </span>
                                {:else if selectedOptionIndex === activePreviewSample.correctIndex}
                                    <span
                                        class="font-medium text-emerald-700 dark:text-emerald-400"
                                    >
                                        {t(
                                            'ui.welcome.features.preview_correct',
                                        )}
                                    </span>
                                {:else}
                                    <span
                                        class="font-medium text-red-600 dark:text-red-400"
                                    >
                                        {t(
                                            'ui.welcome.features.preview_incorrect',
                                            {
                                                answer: activePreviewSample
                                                    .options[
                                                    activePreviewSample
                                                        .correctIndex
                                                ],
                                            },
                                        )}
                                    </span>
                                {/if}

                                {#if selectedOptionIndex !== null}
                                    <button
                                        type="button"
                                        onclick={() =>
                                            (selectedOptionIndex = null)}
                                        class="text-foreground shrink-0 cursor-pointer font-semibold transition-colors duration-150 hover:text-blue-600 motion-reduce:transition-none dark:hover:text-blue-400"
                                    >
                                        {t(
                                            'ui.welcome.features.preview_try_again',
                                        )}
                                    </button>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: 4 Editorial Feature Rows -->
                <div class="lg:col-span-7">
                    <div class="border-border divide-border divide-y border-y">
                        {#each featureRows as row (row.titleKey)}
                            <div
                                class="flex items-start gap-5 py-7 first:pt-6 last:pb-6 sm:gap-6 sm:py-8"
                            >
                                <div
                                    class="mt-0.5 shrink-0 text-blue-600 dark:text-blue-400"
                                >
                                    <row.icon class="size-6" />
                                </div>
                                <div class="space-y-2">
                                    <h3
                                        class="text-foreground text-base font-bold"
                                    >
                                        {t(row.titleKey)}
                                    </h3>
                                    <p
                                        class="text-muted-foreground max-w-[58ch] text-base leading-relaxed"
                                    >
                                        {t(row.descKey)}
                                    </p>
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Band 4: 7-Level Curriculum Ledger -->
    <section
        id="levels"
        class="border-border/80 bg-muted/30 border-t py-20 sm:py-28"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <h2
                    class="text-foreground text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl"
                >
                    {t('ui.welcome.levels.title')}
                </h2>
                <p
                    class="text-muted-foreground mt-4 max-w-[54ch] text-base leading-relaxed"
                >
                    {t('ui.welcome.levels.subtitle')}
                </p>
            </div>

            {#if levels.length > 0}
                <div
                    class="border-border divide-border mt-12 divide-y border-y"
                >
                    {#each levels.slice(0, 7) as level (level.id)}
                        {@const parsed = parseLevelInfo(level)}
                        <Link
                            href={levelsShow.url(level.id)}
                            class="group hover:bg-muted/40 grid grid-cols-1 items-center gap-3 py-5 transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none sm:grid-cols-12 sm:gap-6 sm:px-3"
                        >
                            <div
                                class="flex items-baseline gap-4 sm:col-span-4"
                            >
                                <span
                                    class="shrink-0 text-sm font-semibold text-blue-600 tabular-nums dark:text-blue-400"
                                >
                                    {t('ui.welcome.levels.level_label', {
                                        id: level.id,
                                    })}
                                </span>
                                <h3
                                    class="text-foreground text-base font-bold transition-colors duration-150 group-hover:text-blue-600 motion-reduce:transition-none dark:group-hover:text-blue-400"
                                >
                                    {parsed.title}
                                </h3>
                            </div>

                            <p
                                class="text-muted-foreground text-base leading-relaxed sm:col-span-5"
                            >
                                {parsed.description}
                            </p>

                            <div
                                class="flex items-center justify-between gap-4 sm:col-span-3 sm:justify-end"
                            >
                                <span
                                    class="text-muted-foreground text-sm tabular-nums"
                                >
                                    {t('ui.welcome.levels.words_count', {
                                        count: (
                                            level.vocabularies_count ?? 1000
                                        ).toLocaleString(),
                                    })} · {t('ui.welcome.levels.groups_count', {
                                        count: level.groups_count ?? 100,
                                    })}
                                </span>

                                <span
                                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 dark:text-blue-400"
                                >
                                    <span>
                                        {t('ui.welcome.levels.explore_level')}
                                    </span>
                                    <ArrowRight
                                        class="size-4 transition-transform duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-1 motion-reduce:transition-none"
                                    />
                                </span>
                            </div>
                        </Link>
                    {/each}
                </div>
            {/if}

            <div
                class="mt-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
            >
                <p class="text-muted-foreground text-base">
                    {t('ui.welcome.levels.browse_all_title')}
                </p>

                <Button
                    asChild
                    variant="outline"
                    size="lg"
                    class="text-base font-semibold"
                >
                    {#snippet children(props)}
                        <Link href={levelsIndex.url()} class={props.class}>
                            <span>
                                {t('ui.welcome.levels.browse_all_action')}
                            </span>
                            <ArrowRight class="size-4" />
                        </Link>
                    {/snippet}
                </Button>
            </div>
        </div>
    </section>

    <!-- Band 5: Full-Bleed Cobalt Horizon CTA -->
    <section
        id="demo"
        class="bg-blue-600 py-20 text-white sm:py-24 dark:bg-blue-700"
    >
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <h2
                class="text-3xl leading-tight font-extrabold tracking-tight text-white sm:text-4xl"
            >
                {t('ui.welcome.cta.title')}
            </h2>
            <p
                class="mx-auto mt-4 max-w-[48ch] text-base leading-relaxed text-blue-50/90"
            >
                {t('ui.welcome.cta.subtitle')}
            </p>

            <div class="mt-8 flex justify-center">
                <Button
                    asChild
                    size="lg"
                    class="h-12 bg-white px-7 text-base font-semibold text-blue-700 shadow-sm transition-colors duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] hover:bg-blue-50 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-blue-600 motion-reduce:transition-none"
                >
                    {#snippet children(props)}
                        <Link href={primaryCtaHref} class={props.class}>
                            <span>{t('ui.welcome.cta.start_now')}</span>
                            <ArrowRight class="size-4.5" />
                        </Link>
                    {/snippet}
                </Button>
            </div>
        </div>
    </section>
</div>

<style>
    .hero-paper-grid {
        background-image:
            linear-gradient(
                to right,
                color-mix(in oklab, var(--border) 55%, transparent) 1px,
                transparent 1px
            ),
            linear-gradient(
                to bottom,
                color-mix(in oklab, var(--border) 55%, transparent) 1px,
                transparent 1px
            );
        background-size: 48px 48px;
        mask-image: linear-gradient(
            to bottom,
            rgba(0, 0, 0, 0.85) 0%,
            rgba(0, 0, 0, 0.4) 55%,
            transparent 100%
        );
    }
</style>
