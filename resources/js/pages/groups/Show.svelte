<script lang="ts">
    import { onMount } from 'svelte';
    import { Link, router, setLayoutProps } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Circle from '@lucide/svelte/icons/circle';
    import Clock from '@lucide/svelte/icons/clock';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import Zap from '@lucide/svelte/icons/zap';
    import {
        intro as introGroup,
        quiz as quizGroup,
        show as showGroup,
    } from '@/actions/App/Http/Controllers/GroupController';
    import AppHead from '@/components/AppHead.svelte';
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import {
        determineLocalGroupStatus,
        learningProgressDb,
        type GroupProgressSummary,
    } from '@/lib/learningProgressDb';
    import type { BreadcrumbItem } from '@/types';

    interface GroupData {
        id: number;
        sequence: number;
        level_id: number;
        level_name: string;
        vocabularies_count: number;
        prev_group_id?: number | null;
        next_group_id?: number | null;
    }

    interface VocabularyData {
        id: number;
        word: string;
        part_of_speech: string;
        pronunciation: string;
        definition: string;
        example_sentence: string;
        example_translation: string;
        word_audio_url: string;
        sentence_audio_url: string;
    }

    let {
        group,
        vocabularies = [],
        progress = null,
    }: {
        group: GroupData;
        vocabularies?: VocabularyData[];
        progress?: GroupProgressSummary | null;
    } = $props();

    let focusedIndex = $state(0);
    let localProgress = $state<GroupProgressSummary | null>(null);
    let cardElements: HTMLElement[] = $state([]);

    const currentProgress = $derived(
        progress ??
            localProgress ?? {
                status: 'not_started',
                stage: 0,
                next_review_at: null,
                cooldown_remaining_seconds: null,
            },
    );

    // svelte-ignore state_referenced_locally
    let cooldownSeconds = $state(progress?.cooldown_remaining_seconds ?? 0);

    // svelte-ignore state_referenced_locally
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('ui.group_detail.breadcrumb_levels'),
            href: '/levels',
        },
        {
            title: group.level_name,
            href: `/levels/${group.level_id}`,
        },
        {
            title: t('ui.group_detail.breadcrumb_group', {
                sequence: group.sequence,
            }),
            href: `/groups/${group.id}`,
        },
    ];

    setLayoutProps({
        breadcrumbs,
        showFooter: false,
    });

    function formatCooldownTime(totalSeconds: number): string {
        if (totalSeconds <= 0) {
            return t('ui.time.seconds', { count: 0 });
        }

        const days = Math.floor(totalSeconds / 86400);
        if (days > 0) {
            return t('ui.time.days', { count: days });
        }

        const hours = Math.floor(totalSeconds / 3600);
        if (hours > 0) {
            return t('ui.time.hours', { count: hours });
        }

        const minutes = Math.floor(totalSeconds / 60);
        if (minutes > 0) {
            return t('ui.time.minutes', { count: minutes });
        }

        return t('ui.time.seconds', { count: totalSeconds });
    }

    function focusCard(index: number): void {
        if (index < 0 || index >= vocabularies.length) {
            return;
        }
        focusedIndex = index;
        cardElements[index]?.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
        });
    }

    function handleKeyDown(event: KeyboardEvent): void {
        const target = event.target as HTMLElement | null;
        if (
            target &&
            ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
        ) {
            return;
        }

        if (event.code === 'Space') {
            event.preventDefault();
            const current = vocabularies[focusedIndex];
            if (current) {
                audioPlayer.play(current.word_audio_url, `word-${current.id}`);
            }
        } else if (event.key === 's' || event.key === 'S') {
            event.preventDefault();
            const current = vocabularies[focusedIndex];
            if (current) {
                audioPlayer.play(
                    current.sentence_audio_url,
                    `sentence-${current.id}`,
                );
            }
        } else if (event.key === 'ArrowDown' || event.key === 'j') {
            event.preventDefault();
            focusCard(Math.min(vocabularies.length - 1, focusedIndex + 1));
        } else if (event.key === 'ArrowUp' || event.key === 'k') {
            event.preventDefault();
            focusCard(Math.max(0, focusedIndex - 1));
        }
    }

    function preloadVocabulariesAudio(): void {
        if (vocabularies.length === 0) {
            return;
        }

        const urls = vocabularies.flatMap((v) => [
            v.word_audio_url,
            v.sentence_audio_url,
        ]);
        audioPlayer.preload(urls);
    }

    function startCooldownCountdown(
        activeProgress: GroupProgressSummary | null,
    ): (() => void) | void {
        const initialRemaining =
            activeProgress?.cooldown_remaining_seconds ?? 0;
        if (initialRemaining <= 0) {
            return;
        }

        cooldownSeconds = initialRemaining;

        const targetTime = activeProgress?.next_review_at
            ? new Date(activeProgress.next_review_at).getTime()
            : Date.now() + initialRemaining * 1000;

        const timer = setInterval(() => {
            const remaining = Math.max(
                0,
                Math.ceil((targetTime - Date.now()) / 1000),
            );
            cooldownSeconds = remaining;

            if (remaining <= 0) {
                clearInterval(timer);
            }
        }, 1000);

        return () => clearInterval(timer);
    }

    onMount(() => {
        preloadVocabulariesAudio();

        let cleanupCooldown: (() => void) | void;
        let isMounted = true;

        if (progress !== null) {
            cleanupCooldown = startCooldownCountdown(progress);
        } else {
            void learningProgressDb
                .getGroupProgress(group.id)
                .then((record) => {
                    if (!isMounted) {
                        return;
                    }

                    if (!record || record.stage < 1) {
                        router.visit(introGroup.url(group.id), {
                            replace: true,
                        });
                        return;
                    }

                    const resolved = determineLocalGroupStatus(record);
                    localProgress = resolved;
                    cleanupCooldown = startCooldownCountdown(resolved);
                });
        }

        return () => {
            isMounted = false;
            cleanupCooldown?.();
        };
    });
</script>

<svelte:window onkeydown={handleKeyDown} />

<AppHead
    title={t('ui.group_detail.title', {
        level_name: group.level_name,
        sequence: group.sequence,
    })}
/>

<div
    class="mx-auto max-w-4xl px-4 pt-6 pb-32 selection:bg-blue-600 selection:text-white sm:px-6 sm:pt-8 sm:pb-36"
>
    <!-- Breadcrumb -->
    <div class="mb-4 sm:mb-6">
        <Breadcrumbs {breadcrumbs} />
    </div>

    <!-- Header Section: Title, Status, SRS Stage, Prev/Next Navigation (Idea A: Two-tier layout) -->
    <header class="border-border/70 mb-8 border-b pb-6">
        <!-- Top Row: Title on Left, Prev/Next Navigation on Right -->
        <div class="flex items-center justify-between gap-4">
            <h1
                class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
            >
                {t('ui.group_detail.title', {
                    level_name: group.level_name,
                    sequence: group.sequence,
                })}
            </h1>

            <!-- Prev / Next Group Navigation -->
            <nav
                class="flex shrink-0 items-center gap-2"
                aria-label="Adjacent group navigation"
            >
                {#if group.prev_group_id}
                    <Link
                        href={showGroup.url(group.prev_group_id)}
                        class="border-border bg-card hover:bg-muted/80 text-foreground inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-2 text-sm font-semibold shadow-2xs transition-all active:scale-[0.98]"
                    >
                        <ChevronLeft class="size-4" />
                        <span>{t('ui.group_detail.prev_group')}</span>
                    </Link>
                {:else}
                    <button
                        type="button"
                        disabled
                        class="border-border/50 bg-muted/30 text-muted-foreground/40 inline-flex cursor-not-allowed items-center gap-1.5 rounded-xl border px-3.5 py-2 text-sm font-semibold"
                    >
                        <ChevronLeft class="size-4" />
                        <span>{t('ui.group_detail.prev_group')}</span>
                    </button>
                {/if}

                {#if group.next_group_id}
                    <Link
                        href={showGroup.url(group.next_group_id)}
                        class="border-border bg-card hover:bg-muted/80 text-foreground inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-2 text-sm font-semibold shadow-2xs transition-all active:scale-[0.98]"
                    >
                        <span>{t('ui.group_detail.next_group')}</span>
                        <ChevronRight class="size-4" />
                    </Link>
                {:else}
                    <button
                        type="button"
                        disabled
                        class="border-border/50 bg-muted/30 text-muted-foreground/40 inline-flex cursor-not-allowed items-center gap-1.5 rounded-xl border px-3.5 py-2 text-sm font-semibold"
                    >
                        <span>{t('ui.group_detail.next_group')}</span>
                        <ChevronRight class="size-4" />
                    </button>
                {/if}
            </nav>
        </div>

        <!-- Second Row: SRS Learning Track (Status Badge + Stage Indicator) -->
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <!-- Status Badge -->
            {#if currentProgress.status === 'completed'}
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300 bg-emerald-50 px-3 py-0.5 text-sm font-semibold text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/50 dark:text-emerald-300"
                >
                    <CheckCircle2
                        class="size-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <span>{t('ui.group_detail.status.completed')}</span>
                </span>
            {:else if currentProgress.status === 'ready' || (currentProgress.status === 'cooldown' && cooldownSeconds <= 0)}
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-blue-300 bg-blue-50 px-3 py-0.5 text-sm font-semibold text-blue-800 dark:border-blue-800/60 dark:bg-blue-950/50 dark:text-blue-300"
                >
                    <Zap class="size-4 text-blue-600 dark:text-blue-400" />
                    <span>{t('ui.group_detail.status.ready')}</span>
                </span>
            {:else if currentProgress.status === 'cooldown' && cooldownSeconds > 0}
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-amber-50 px-3 py-0.5 text-sm font-semibold text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/50 dark:text-amber-300"
                >
                    <Clock class="size-4 text-amber-600 dark:text-amber-400" />
                    <span>{t('ui.group_detail.status.cooldown')}</span>
                </span>
            {:else}
                <span
                    class="border-border bg-muted/60 text-muted-foreground inline-flex items-center gap-1.5 rounded-full border px-3 py-0.5 text-sm font-medium"
                >
                    <Circle class="size-3.5 stroke-[1.5]" />
                    <span>{t('ui.group_detail.status.not_started')}</span>
                </span>
            {/if}

            <!-- 6-Stage Indicator & Progress Text (only when stage > 0) -->
            {#if currentProgress.stage > 0}
                <span class="text-border select-none">·</span>
                <div
                    class="flex items-center gap-2"
                    title={currentProgress.stage >= 6
                        ? t('ui.group_detail.stage_completed')
                        : t('ui.group_detail.stage_indicator', {
                              stage: currentProgress.stage,
                          })}
                >
                    <div class="flex items-center gap-1">
                        {#each [1, 2, 3, 4, 5, 6] as step}
                            <span
                                class={{
                                    'size-2 rounded-full transition-colors': true,
                                    'bg-emerald-600 dark:bg-emerald-400':
                                        currentProgress.stage >= step &&
                                        currentProgress.status === 'completed',
                                    'bg-blue-600 dark:bg-blue-400':
                                        currentProgress.stage >= step &&
                                        currentProgress.status !== 'completed',
                                    'bg-muted-foreground/25 dark:bg-muted-foreground/30':
                                        currentProgress.stage < step,
                                }}
                            ></span>
                        {/each}
                    </div>
                    <span class="text-muted-foreground text-sm font-medium">
                        {currentProgress.stage >= 6
                            ? t('ui.group_detail.stage_completed')
                            : t('ui.group_detail.stage_indicator', {
                                  stage: currentProgress.stage,
                              })}
                    </span>
                </div>
            {/if}
        </div>
    </header>

    <!-- 10 Vocabulary Cards List -->
    <div class="space-y-4">
        {#each vocabularies as item, index (item.id)}
            <section
                bind:this={cardElements[index]}
                class={{
                    'bg-card scroll-my-24 rounded-2xl border p-5 transition-all duration-200 sm:p-6': true,
                    'border-border/70 hover:border-border shadow-2xs':
                        focusedIndex !== index,
                    'border-blue-500/80 shadow-xs ring-2 ring-blue-500/20 dark:border-blue-500/70 dark:ring-blue-500/30':
                        focusedIndex === index,
                }}
                aria-label={item.word}
                onpointerdown={() => (focusedIndex = index)}
                onfocusin={() => (focusedIndex = index)}
            >
                <!-- Card Header: Index, Word, POS, Pronunciation, Word Audio Button -->
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-wrap items-baseline gap-2.5 sm:gap-3">
                        <!-- 01~10 Index Badge -->
                        <span
                            class="text-muted-foreground/60 font-mono text-sm font-bold select-none"
                        >
                            {String(index + 1).padStart(2, '0')}
                        </span>

                        <!-- Target Word -->
                        <h2
                            class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
                        >
                            {item.word}
                        </h2>

                        <!-- Part of Speech Pill -->
                        <span
                            class="bg-muted text-foreground/80 rounded-md px-2.5 py-0.5 text-sm font-semibold"
                        >
                            {item.part_of_speech}
                        </span>

                        <!-- IPA Pronunciation -->
                        <span
                            class="text-muted-foreground font-mono text-sm sm:text-base"
                        >
                            {item.pronunciation}
                        </span>
                    </div>

                    <!-- 40x40px Word Audio Button (strictly aligned to right rail) -->
                    <button
                        type="button"
                        onclick={(e) => {
                            e.stopPropagation();
                            focusedIndex = index;
                            audioPlayer.play(
                                item.word_audio_url,
                                `word-${item.id}`,
                            );
                        }}
                        class={{
                            'flex size-10 shrink-0 items-center justify-center rounded-full border transition-all duration-150 active:scale-95': true,
                            'animate-pulse border-blue-600 bg-blue-600 text-white shadow-xs ring-4 ring-blue-500/20':
                                audioPlayer.isPlaying(`word-${item.id}`),
                            'border-border/70 bg-muted/40 text-muted-foreground dark:bg-muted/30 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:bg-blue-950/60 dark:hover:text-blue-300':
                                !audioPlayer.isPlaying(`word-${item.id}`),
                        }}
                        title={t('ui.group_detail.play_word_audio')}
                        aria-label={t('ui.group_detail.play_word_audio')}
                    >
                        <Volume2 class="size-4.5" />
                    </button>
                </div>

                <!-- Definition -->
                <p
                    class="text-foreground mt-2.5 text-base font-medium sm:text-lg"
                >
                    {item.definition}
                </p>

                <!-- Integrated Example Sentence Section (strictly aligned to right rail) -->
                <div class="border-border/60 mt-4 border-t pt-3.5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <p
                                class="text-foreground text-sm leading-relaxed font-medium sm:text-base"
                            >
                                {item.example_sentence}
                            </p>
                            <p
                                class="text-muted-foreground text-sm leading-normal"
                            >
                                {item.example_translation}
                            </p>
                        </div>

                        <!-- 40x40px Sentence Audio Button (strictly aligned with word audio button) -->
                        <button
                            type="button"
                            onclick={(e) => {
                                e.stopPropagation();
                                focusedIndex = index;
                                audioPlayer.play(
                                    item.sentence_audio_url,
                                    `sentence-${item.id}`,
                                );
                            }}
                            class={{
                                'flex size-10 shrink-0 items-center justify-center rounded-full border transition-all duration-150 active:scale-95': true,
                                'animate-pulse border-blue-600 bg-blue-600 text-white shadow-xs ring-4 ring-blue-500/20':
                                    audioPlayer.isPlaying(
                                        `sentence-${item.id}`,
                                    ),
                                'border-border/70 bg-muted/40 text-muted-foreground dark:bg-muted/30 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 dark:hover:border-blue-800/60 dark:hover:bg-blue-950/60 dark:hover:text-blue-300':
                                    !audioPlayer.isPlaying(
                                        `sentence-${item.id}`,
                                    ),
                            }}
                            title={t('ui.group_detail.play_sentence_audio')}
                            aria-label={t(
                                'ui.group_detail.play_sentence_audio',
                            )}
                        >
                            <Volume2 class="size-4.5" />
                        </button>
                    </div>
                </div>
            </section>
        {/each}
    </div>
</div>

<!-- Sticky Floating Bottom Action Bar -->
<aside
    class="border-border/80 bg-background/95 fixed inset-x-0 bottom-0 z-30 border-t px-4 py-3.5 backdrop-blur-md sm:px-6"
    aria-label="Group action footer"
>
    <div class="mx-auto flex max-w-4xl items-center justify-between gap-4">
        <!-- Summary & Keyboard Shortcuts -->
        <div class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-4">
            <span class="text-foreground text-sm font-semibold">
                {t('ui.group_detail.sticky_bar.summary', {
                    sequence: group.sequence,
                    count: vocabularies.length,
                })}
            </span>

            <span
                class="text-muted-foreground hidden items-center gap-1.5 text-sm lg:inline-flex"
            >
                <span class="text-border">·</span>
                <span>{t('ui.group_detail.keyboard_hint')}</span>
            </span>
        </div>

        <!-- Action Button on the right -->
        {#if currentProgress.status === 'not_started'}
            <Link
                href={introGroup.url(group.id)}
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-[0.98] sm:px-8 sm:py-3 sm:text-base dark:bg-blue-600 dark:hover:bg-blue-500"
            >
                <span>{t('ui.group_detail.sticky_bar.start_stage_1')}</span>
                <ArrowRight class="size-4" />
            </Link>
        {:else if currentProgress.status === 'cooldown' && cooldownSeconds > 0}
            <button
                type="button"
                disabled
                class="bg-muted text-muted-foreground inline-flex cursor-not-allowed items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-semibold sm:px-8 sm:py-3 sm:text-base"
            >
                <Clock
                    class={{
                        'size-4.5': true,
                        'animate-pulse text-amber-500': cooldownSeconds <= 60,
                    }}
                />
                <span class={{ 'font-mono': cooldownSeconds <= 60 }}>
                    {t('ui.group_detail.sticky_bar.cooldown', {
                        time: formatCooldownTime(cooldownSeconds),
                    })}
                </span>
            </button>
        {:else if currentProgress.status === 'completed'}
            <Link
                href={quizGroup.url(group.id)}
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-emerald-700 active:scale-[0.98] sm:px-8 sm:py-3 sm:text-base dark:bg-emerald-600 dark:hover:bg-emerald-500"
            >
                <span>{t('ui.group_detail.sticky_bar.mastered')}</span>
                <ArrowRight class="size-4" />
            </Link>
        {:else}
            <Link
                href={quizGroup.url(group.id)}
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-[0.98] sm:px-8 sm:py-3 sm:text-base dark:bg-blue-600 dark:hover:bg-blue-500"
            >
                <span
                    >{t('ui.group_detail.sticky_bar.start_review', {
                        stage: Math.min(6, currentProgress.stage + 1),
                    })}</span
                >
                <ArrowRight class="size-4" />
            </Link>
        {/if}
    </div>
</aside>
