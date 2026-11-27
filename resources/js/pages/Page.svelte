<script lang="ts">
    import { Link, setLayoutProps } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Brain from '@lucide/svelte/icons/brain';
    import Cloud from '@lucide/svelte/icons/cloud';
    import Layers from '@lucide/svelte/icons/layers';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Smartphone from '@lucide/svelte/icons/smartphone';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import SentenceClozeCard from '@/components/quiz/SentenceClozeCard.svelte';
    import SpellingCard from '@/components/quiz/SpellingCard.svelte';
    import VocabularyIntroCard from '@/components/quiz/VocabularyIntroCard.svelte';
    import { Button } from '@/components/ui/button';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import { index as levelsIndex, show as levelsShow } from '@/routes/levels';
    import type {
        QuizOptionPayload,
        QuizQuestionPayload,
        SpellingQuestionPayload,
        VocabularyPayload,
    } from '@/types/quiz';

    setLayoutProps({
        headerOverlay: true,
    });

    interface LevelData {
        id: number;
        name: string;
        description: string;
        vocabularies_count?: number;
        groups_count?: number;
    }

    interface WelcomeShowcaseData {
        vocabulary: VocabularyPayload;
        cloze_question: QuizQuestionPayload | null;
        spelling_question: SpellingQuestionPayload;
    }

    let {
        levels = [],
        showcase = null,
    }: {
        levels?: LevelData[];
        showcase?: WelcomeShowcaseData | null;
    } = $props();

    const fallbackVocabulary = $derived<VocabularyPayload>({
        id: 0,
        word: t('ui.welcome.hero.fallback.word'),
        part_of_speech: t('ui.welcome.hero.fallback.part_of_speech'),
        pronunciation: t('ui.welcome.hero.fallback.pronunciation'),
        definition: t('ui.welcome.hero.fallback.definition'),
        example_sentence: t('ui.welcome.hero.fallback.example_sentence'),
        example_translation: t('ui.welcome.hero.fallback.example_translation'),
        word_audio_url: '',
        sentence_audio_url: '',
    });

    const fallbackClozeQuestion = $derived<QuizQuestionPayload>({
        quiz_type: 'sentence_cloze',
        stem: 'Her {{blank}} helped her recover quickly from the setback.',
        audio_url: '',
        correct_option_id: 1,
        sentence_segments: [
            { type: 'text', text: 'Her ' },
            { type: 'blank', text: 'resilience' },
            {
                type: 'text',
                text: ' helped her recover quickly from the setback.',
            },
        ],
        options: [
            {
                id: 1,
                text: 'resilience',
                part_of_speech: 'n.',
                word: 'resilience',
                definition: t('ui.welcome.hero.fallback.definition'),
            },
            {
                id: 2,
                text: 'hesitation',
                part_of_speech: 'n.',
                word: 'hesitation',
                definition: '',
            },
            {
                id: 3,
                text: 'curiosity',
                part_of_speech: 'n.',
                word: 'curiosity',
                definition: '',
            },
            {
                id: 4,
                text: 'obstacle',
                part_of_speech: 'n.',
                word: 'obstacle',
                definition: '',
            },
        ],
    });

    const fallbackSpellingQuestion = $derived<SpellingQuestionPayload>({
        quiz_type: 'spelling',
        stem: fallbackVocabulary.definition,
        audio_url: '',
        target_word: 'resilience',
        slots: [
            { is_blank: false, char: 'r' },
            { is_blank: false, char: 'e' },
            { is_blank: true, char: 's' },
            { is_blank: false, char: 'i' },
            { is_blank: true, char: 'l' },
            { is_blank: false, char: 'i' },
            { is_blank: true, char: 'e' },
            { is_blank: false, char: 'n' },
            { is_blank: true, char: 'c' },
            { is_blank: false, char: 'e' },
        ],
        tiles: [
            { slot_index: 8, char: 'c' },
            { slot_index: 6, char: 'e' },
            { slot_index: 4, char: 'l' },
            { slot_index: 2, char: 's' },
        ],
    });

    const introVocabulary = $derived(
        showcase?.vocabulary ?? fallbackVocabulary,
    );
    const clozeVocabulary = $derived(
        showcase?.cloze_question ? showcase.vocabulary : fallbackVocabulary,
    );
    const clozeQuestion = $derived(
        showcase?.cloze_question ?? fallbackClozeQuestion,
    );
    const spellingVocabulary = $derived(
        showcase?.vocabulary ?? fallbackVocabulary,
    );
    const spellingQuestion = $derived(
        showcase?.spelling_question ?? fallbackSpellingQuestion,
    );

    type WorkbenchMode = 'intro' | 'cloze' | 'spelling';
    let workbenchMode = $state<WorkbenchMode>('intro');
    let selectedClozeOptionId = $state<number | null>(null);
    let spellingAnswered = $state(false);
    let spellingKey = $state(0);

    function switchWorkbenchMode(mode: WorkbenchMode): void {
        audioPlayer.stop();
        workbenchMode = mode;
        selectedClozeOptionId = null;
        spellingAnswered = false;
        if (mode === 'spelling') {
            spellingKey += 1;
        }
    }

    function retryWorkbenchQuiz(): void {
        audioPlayer.stop();
        if (workbenchMode === 'cloze') {
            selectedClozeOptionId = null;
        } else if (workbenchMode === 'spelling') {
            spellingAnswered = false;
            spellingKey += 1;
        }
    }

    onMount(() => {
        if (
            introVocabulary.word_audio_url &&
            introVocabulary.sentence_audio_url &&
            window.location.hostname.endsWith('vomorize.com')
        ) {
            void audioPlayer.preload([
                introVocabulary.word_audio_url,
                introVocabulary.sentence_audio_url,
            ]);
        }

        return () => {
            audioPlayer.stop();
        };
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
        { stage: 1, cx: 120, cy: 43, dayLabel: '1d' },
        { stage: 2, cx: 196, cy: 40, dayLabel: '3d' },
        { stage: 3, cx: 288, cy: 38, dayLabel: '7d' },
        { stage: 4, cx: 402, cy: 38, dayLabel: '14d' },
        { stage: 5, cx: 526, cy: 35, dayLabel: '30d' },
        { stage: 6, cx: 644, cy: 33, dayLabel: '55d+' },
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

    // Band 3: Editorial Feature Rows
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

    const primaryCtaHref = $derived(
        levels.length > 0 ? levelsShow.url(levels[0].id) : levelsIndex.url(),
    );
</script>

<AppHead title={t('ui.welcome.hero.title')} />

<div class="selection:bg-blue-600 selection:text-white">
    <!-- Band 1: Hero & Real Interactive Learning Card Showcase -->
    <section class="relative overflow-hidden pt-28 pb-20 sm:pt-32 sm:pb-24">
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
                        <Link href={levelsIndex.url()} class={props.class}>
                            <span>{t('ui.welcome.hero.start_learning')}</span>
                            <ArrowRight class="size-4.5" />
                        </Link>
                    {/snippet}
                </Button>
            </div>
        </div>

        <!-- Real Learning Card Stage (matches actual Intro / Cloze / Spelling cards) -->
        <div
            class="relative mx-auto mt-12 max-w-xl px-4 sm:mt-14 sm:px-0"
            data-test="hero-workbench"
        >
            <div class="flex justify-center">
                <div
                    role="tablist"
                    aria-label={t('ui.welcome.hero.workbench_label')}
                    class="border-border/80 bg-muted/75 inline-flex rounded-xl border p-1 backdrop-blur-xs"
                >
                    {#each ['intro', 'cloze', 'spelling'] as const as mode (mode)}
                        {@const isActive = workbenchMode === mode}
                        <button
                            type="button"
                            role="tab"
                            aria-selected={isActive}
                            onclick={() => switchWorkbenchMode(mode)}
                            class={{
                                'cursor-pointer rounded-lg px-3.5 py-1.5 text-sm font-semibold transition-[background-color,color,box-shadow] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none sm:px-4': true,
                                'bg-background text-foreground shadow-xs':
                                    isActive,
                                'text-muted-foreground hover:text-foreground':
                                    !isActive,
                            }}
                        >
                            {t(`ui.welcome.hero.modes.${mode}`)}
                        </button>
                    {/each}
                </div>
            </div>

            <div class="mt-4 text-left" data-test="hero-workbench-card">
                {#if workbenchMode === 'intro'}
                    <VocabularyIntroCard
                        vocabulary={introVocabulary}
                        autoPlay={false}
                    />
                {:else if workbenchMode === 'cloze'}
                    <SentenceClozeCard
                        question={clozeQuestion}
                        vocabulary={clozeVocabulary}
                        selectedOptionId={selectedClozeOptionId}
                        onSelect={(option: QuizOptionPayload) => {
                            selectedClozeOptionId = option.id;
                        }}
                    />
                {:else}
                    {#key spellingKey}
                        <SpellingCard
                            question={spellingQuestion}
                            vocabulary={spellingVocabulary}
                            autoPlay={false}
                            onResult={() => {
                                spellingAnswered = true;
                            }}
                        />
                    {/key}
                {/if}
            </div>

            <div class="mt-3 flex h-9 items-center justify-center">
                {#if (workbenchMode === 'cloze' && selectedClozeOptionId !== null) || (workbenchMode === 'spelling' && spellingAnswered)}
                    <button
                        type="button"
                        onclick={retryWorkbenchQuiz}
                        class="text-muted-foreground hover:text-foreground inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none"
                    >
                        <RotateCcw class="size-4" />
                        <span>{t('ui.welcome.hero.retry')}</span>
                    </button>
                {/if}
            </div>
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

    <!-- Band 3: Editorial Feature Craft -->
    <section id="features" class="py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                class="grid grid-cols-1 items-start gap-12 lg:grid-cols-12 lg:gap-16"
            >
                <!-- Left Sticky Column: Heading & Summary -->
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

    <!-- Band 4: Curriculum Ledger -->
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
