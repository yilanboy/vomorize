<script lang="ts">
    import { onMount } from 'svelte';
    import { Link, router, setLayoutProps } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Clock from '@lucide/svelte/icons/clock';
    import { show as showGroup } from '@/actions/App/Http/Controllers/GroupController';
    import { show as introGroup } from '@/actions/App/Http/Controllers/GroupIntroController';
    import { show as quizGroup } from '@/actions/App/Http/Controllers/GroupQuizController';
    import AppHead from '@/components/AppHead.svelte';
    import VocabularyCard from '@/components/VocabularyCard.svelte';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import {
        determineLocalGroupStatus,
        learningProgressDb,
        type GroupProgressSummary,
    } from '@/lib/learningProgressDb';
    import { index as levelsIndex, show as showLevel } from '@/routes/levels';
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

    let localProgress = $state<GroupProgressSummary | null>(null);

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

    const breadcrumbs: BreadcrumbItem[] = $derived([
        {
            title: t('ui.group_detail.breadcrumb_levels'),
            href: levelsIndex.url(),
        },
        {
            title: group.level_name,
            href: showLevel.url(group.level_id),
        },
        {
            title: t('ui.group_detail.breadcrumb_group', {
                sequence: group.sequence,
            }),
            href: showGroup.url(group.id),
        },
    ]);

    $effect.pre(() => {
        setLayoutProps({
            breadcrumbs,
            showFooter: false,
        });
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

<AppHead
    title={t('ui.group_detail.title', {
        level_name: group.level_name,
        sequence: group.sequence,
    })}
/>

<div
    class="mx-auto max-w-4xl px-4 pt-6 pb-32 selection:bg-blue-600 selection:text-white sm:px-6 sm:pt-8 sm:pb-36"
>
    <!-- Header Section: Title, SRS Stage, Prev/Next Navigation -->
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

        <!-- Second Row: 6-Stage SRS Progress Track -->
        {#if currentProgress.stage > 0}
            <div
                class="mt-3 flex flex-wrap items-center gap-3"
                title={currentProgress.stage >= 6
                    ? t('ui.group_detail.stage_completed')
                    : t('ui.group_detail.stage_indicator', {
                          stage: currentProgress.stage,
                      })}
            >
                <div class="flex items-center gap-1.5">
                    {#each [1, 2, 3, 4, 5, 6] as step (step)}
                        <span
                            class={{
                                'h-2 w-6 rounded-full transition-colors': true,
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
    </header>

    <!-- 10 Vocabulary Cards List -->
    <div class="space-y-4">
        {#each vocabularies as item, index (item.id)}
            <VocabularyCard vocabulary={item} {index} />
        {/each}
    </div>
</div>

<!-- Sticky Floating Bottom Action Bar -->
<aside
    class="border-border/80 bg-background/95 fixed inset-x-0 bottom-0 z-30 border-t px-4 py-3.5 backdrop-blur-md sm:px-6"
    aria-label="Group action footer"
>
    <div class="mx-auto flex max-w-4xl items-center justify-between gap-4">
        <!-- Summary -->
        <div class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-4">
            <span class="text-foreground text-sm font-semibold">
                {t('ui.group_detail.sticky_bar.summary', {
                    sequence: group.sequence,
                    count: vocabularies.length,
                })}
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
                class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-6 py-2.5 text-sm font-semibold text-amber-800 sm:px-8 sm:py-3 sm:text-base dark:border-amber-800/60 dark:bg-amber-950/50 dark:text-amber-300"
            >
                <Clock
                    class={{
                        'size-4.5 text-amber-600 dark:text-amber-400': true,
                        'animate-pulse': cooldownSeconds <= 60,
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
                <span>{t('ui.group_detail.sticky_bar.start_review')}</span>
                <ArrowRight class="size-4" />
            </Link>
        {/if}
    </div>
</aside>
