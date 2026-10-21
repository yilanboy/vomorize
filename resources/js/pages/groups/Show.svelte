<script lang="ts">
    import { onMount } from 'svelte';
    import { setLayoutProps } from '@inertiajs/svelte';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import Circle from '@lucide/svelte/icons/circle';
    import Clock from '@lucide/svelte/icons/clock';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import Zap from '@lucide/svelte/icons/zap';
    import AppHead from '@/components/AppHead.svelte';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type { BreadcrumbItem } from '@/types';

    interface GroupData {
        id: number;
        sequence: number;
        level_id: number;
        level_name: string;
        vocabularies_count: number;
    }

    interface GroupProgressData {
        status: 'not_started' | 'cooldown' | 'ready' | 'completed';
        stage: number;
        next_review_at: string | null;
        cooldown_remaining_seconds: number | null;
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
        progress?: GroupProgressData | null;
    } = $props();

    let focusedIndex = $state(0);

    const currentProgress = $derived(
        progress ?? {
            status: 'not_started',
            stage: 0,
            next_review_at: null,
            cooldown_remaining_seconds: null,
        },
    );

    // svelte-ignore state_referenced_locally
    let cooldownSeconds = $state(progress?.cooldown_remaining_seconds ?? 0);

    onMount(() => {
        if (cooldownSeconds <= 0) return;

        const targetTime = progress?.next_review_at
            ? new Date(progress.next_review_at).getTime()
            : Date.now() + cooldownSeconds * 1000;

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
    });

    // svelte-ignore state_referenced_locally
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('ui.group_detail.breadcrumb_levels'),
            href: '/levels',
        },
        {
            title: t('ui.group_detail.breadcrumb_level', {
                id: group.level_id,
            }),
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
    });

    // Background preload all 10 word audio and sentence audio files via Web Audio API
    $effect(() => {
        if (vocabularies.length > 0) {
            const urls = vocabularies.flatMap((v) => [
                v.word_audio_url,
                v.sentence_audio_url,
            ]);
            audioPlayer.preload(urls);
        }
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

    // Keyboard shortcuts (Space: play word audio, S: play sentence audio)
    function handleKeyDown(event: KeyboardEvent) {
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
        }
    }
</script>

<svelte:window onkeydown={handleKeyDown} />

<AppHead
    title={t('ui.group_detail.title', {
        level_id: group.level_id,
        sequence: group.sequence,
    })}
/>

<div class="mx-auto max-w-4xl px-4 pt-6 pb-32 sm:px-6 sm:pt-8 sm:pb-36">
    <!-- Header: Title & Status Badge Only (No Subtitle, No Extra Back Link, No Prev/Next Buttons) -->
    <header
        class="border-border/60 mb-6 flex flex-wrap items-center justify-between gap-4 border-b pb-5"
    >
        <div class="flex flex-wrap items-center gap-3.5">
            <h1
                class="text-foreground text-2xl font-bold tracking-tight sm:text-3xl"
            >
                {t('ui.group_detail.title', {
                    level_id: group.level_id,
                    sequence: group.sequence,
                })}
            </h1>

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
        </div>
    </header>

    <!-- 10 Vocabulary Cards List -->
    <div class="space-y-4">
        {#each vocabularies as item, index (item.id)}
            <section
                class={{
                    'bg-card border-border/80 rounded-2xl border p-5 shadow-2xs transition-all duration-200 sm:p-6': true,
                    'border-blue-400/80 ring-2 ring-blue-500/30':
                        focusedIndex === index,
                }}
                aria-label={item.word}
                onpointerdown={() => (focusedIndex = index)}
                onfocusin={() => (focusedIndex = index)}
            >
                <!-- Card Header: Word, POS, Pronunciation, Word Audio Button -->
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-wrap items-baseline gap-2.5 sm:gap-3">
                        <span
                            class="text-foreground text-2xl font-extrabold tracking-tight sm:text-3xl"
                        >
                            {item.word}
                        </span>

                        <span
                            class="bg-muted text-foreground/80 rounded-md px-2.5 py-0.5 text-sm font-semibold"
                        >
                            {item.part_of_speech}
                        </span>

                        <span
                            class="text-muted-foreground font-mono text-sm sm:text-base"
                        >
                            {item.pronunciation}
                        </span>
                    </div>

                    <!-- 44x44px Word Audio Button -->
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
                            'flex size-11 shrink-0 items-center justify-center rounded-full transition-all duration-150 active:scale-95 sm:size-12': true,
                            'animate-pulse bg-blue-600 text-white shadow-xs ring-4 ring-blue-500/20':
                                audioPlayer.isPlaying(`word-${item.id}`),
                            'bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300 dark:hover:bg-blue-900/60':
                                !audioPlayer.isPlaying(`word-${item.id}`),
                        }}
                        title={t('ui.group_detail.play_word_audio')}
                        aria-label={t('ui.group_detail.play_word_audio')}
                    >
                        <Volume2 class="size-5 sm:size-5.5" />
                    </button>
                </div>

                <!-- Definition -->
                <p
                    class="text-foreground mt-2 text-base font-medium sm:text-lg"
                >
                    {item.definition}
                </p>

                <!-- Example Sentence Box -->
                <div
                    class="bg-muted/40 border-border/50 mt-3.5 rounded-xl border p-3.5 sm:p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p
                            class="text-foreground text-sm leading-relaxed font-medium sm:text-base"
                        >
                            {item.example_sentence}
                        </p>

                        <!-- Sentence Audio Button -->
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
                                'flex size-9 shrink-0 items-center justify-center rounded-full transition-all duration-150 active:scale-95': true,
                                'animate-pulse bg-blue-600 text-white shadow-2xs':
                                    audioPlayer.isPlaying(
                                        `sentence-${item.id}`,
                                    ),
                                'bg-card border-border hover:bg-muted text-muted-foreground border hover:text-blue-600':
                                    !audioPlayer.isPlaying(
                                        `sentence-${item.id}`,
                                    ),
                            }}
                            title={t('ui.group_detail.play_sentence_audio')}
                            aria-label={t(
                                'ui.group_detail.play_sentence_audio',
                            )}
                        >
                            <Volume2 class="size-4" />
                        </button>
                    </div>

                    <p class="text-muted-foreground mt-1.5 text-sm">
                        {item.example_translation}
                    </p>
                </div>
            </section>
        {/each}
    </div>
</div>

<!-- Sticky Floating Bottom Action Bar -->
<aside
    class="bg-background/90 border-border/80 fixed inset-x-0 bottom-0 z-30 border-t p-4 backdrop-blur-md"
    aria-label="Group action footer"
>
    <div class="mx-auto flex max-w-4xl items-center justify-between gap-4">
        <!-- Summary on the left -->
        <span class="text-muted-foreground text-sm font-medium">
            {t('ui.group_detail.sticky_bar.summary', {
                sequence: group.sequence,
                count: vocabularies.length,
            })}
        </span>

        <!-- Action Button on the right -->
        {#if currentProgress.status === 'not_started'}
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-[0.98] sm:px-8 sm:py-3 sm:text-base dark:bg-blue-600 dark:hover:bg-blue-500"
            >
                <span>{t('ui.group_detail.sticky_bar.start_stage_1')}</span>
            </button>
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
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-emerald-700 active:scale-[0.98] sm:px-8 sm:py-3 sm:text-base dark:bg-emerald-600 dark:hover:bg-emerald-500"
            >
                <span>{t('ui.group_detail.sticky_bar.mastered')}</span>
            </button>
        {:else}
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-[0.98] sm:px-8 sm:py-3 sm:text-base dark:bg-blue-600 dark:hover:bg-blue-500"
            >
                <span
                    >{t('ui.group_detail.sticky_bar.start_review', {
                        stage: Math.min(6, currentProgress.stage + 1),
                    })}</span
                >
            </button>
        {/if}
    </div>
</aside>
