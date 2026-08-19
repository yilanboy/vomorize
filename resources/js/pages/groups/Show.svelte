<script lang="ts">
    import { page, Link } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import AudioButton from '@/components/AudioButton.svelte';
    import StickyActionBar from '@/components/StickyActionBar.svelte';
    import { currentLocale, interpolate, localized, translations } from '@/lib/locale.svelte';
    import { deriveGroupStatus, getGuestGroupProgress } from '@/lib/progress';
    import { definitionOf, exampleTranslationOf } from '@/lib/groupQuiz';
    import type { VocabularyItem } from '@/lib/groupQuiz';
    import { home as homeRoute } from '@/routes';
    import { introduce as introduceRoute, quiz as quizRoute } from '@/routes/groups';
    import { show as levelRoute } from '@/routes/levels';

    interface Breadcrumb {
        label: string;
        url?: string;
    }

    interface LevelTranslationItem {
        locale: string;
        name: string;
    }

    interface LevelData {
        id: number;
        translations: LevelTranslationItem[];
    }

    interface GroupData {
        id: number;
        sequence: number;
        level_id: number;
    }

    interface ProgressData {
        stage: number;
        last_score: number | null;
        last_reviewed_at: string | null;
        next_review_at: string | null;
        status: 'not_started' | 'locked' | 'penalty' | 'ready' | 'completed';
    }

    let {
        group,
        level,
        progress,
        vocabularies = [],
    } = $props<{
        group: GroupData;
        level: LevelData;
        progress: ProgressData;
        vocabularies: VocabularyItem[];
    }>();

    let t = $derived(translations());
    let isGuest = $derived(!page.props.auth?.user);

    /**
     * Assembled here rather than sent down ready-made, because a language switch is answered
     * entirely in the browser: labels it the server resolved would sit in whichever language the
     * page happened to arrive in, which is the whole of the defect this replaces.
     */
    let breadcrumbs = $derived<Breadcrumb[]>([
        { label: t['home'], url: homeRoute.url({ locale: currentLocale() }) },
        {
            label: localized<LevelTranslationItem>(level.translations)?.name ?? '',
            url: levelRoute.url({ locale: currentLocale(), level: level.id }),
        },
        { label: interpolate(t['group_title'], { id: String(group.sequence) }) },
    ]);

    let activeProgress: ProgressData = $state({
        stage: 0,
        last_score: null,
        last_reviewed_at: null,
        next_review_at: null,
        status: 'not_started',
    });

    let quizHref = $derived(
        activeProgress.stage > 0
            ? quizRoute.url({ locale: currentLocale(), group: group.id })
            : introduceRoute.url({ locale: currentLocale(), group: group.id }),
    );

    onMount(() => {
        const guestProgress = getGuestGroupProgress(group.id);

        if (!isGuest) {
            activeProgress = {
                ...progress,
                status: deriveGroupStatus(
                    progress.stage,
                    progress.last_score,
                    progress.next_review_at,
                    Date.now(),
                ),
            };

            return;
        } else {
            if (!guestProgress) {
                return;
            }

            activeProgress = {
                stage: guestProgress.stage,
                last_score: guestProgress.last_score,
                last_reviewed_at: guestProgress.last_reviewed_at,
                next_review_at: guestProgress.next_review_at,
                status: deriveGroupStatus(
                    guestProgress.stage,
                    guestProgress.last_score,
                    guestProgress.next_review_at,
                    Date.now(),
                ),
            };
        }
    });
</script>

<div class="flex flex-1 flex-col">
    <main class="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-6">
        <div class="mb-6 flex items-center space-x-3">
            {#each breadcrumbs as breadcrumb, i (breadcrumb.label)}
                {#if breadcrumb.url}
                    <Link
                        href={breadcrumb.url}
                        class="text-sm font-semibold text-zinc-900 underline-offset-4 hover:underline dark:text-zinc-50"
                    >
                        {#if i === 0}&larr;{/if}{breadcrumb.label}
                    </Link>
                {:else}
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400"
                        >{breadcrumb.label}</span
                    >
                {/if}

                {#if i < breadcrumbs.length - 1}
                    <span class="text-sm text-zinc-500/50 dark:text-zinc-400/50">/</span>
                {/if}
            {/each}
        </div>

        <div
            class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div
                class="flex items-start justify-between gap-4 border-b border-zinc-200 pb-4 dark:border-zinc-800"
            >
                <div>
                    <h1
                        data-test="group-title"
                        class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50"
                    >
                        {interpolate(t['group_title'], { id: String(group.sequence) })}
                    </h1>
                    <p
                        data-test="core-vocab-count"
                        class="mt-1 text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        {interpolate(t['core_vocab_count'], { count: String(vocabularies.length) })}
                    </p>
                </div>
                <span
                    data-test="group-status"
                    class="rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
                >
                    {t[activeProgress.status === 'penalty' ? 'cooldown' : activeProgress.status]}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                <div data-test="current-stage">
                    {interpolate(t['current_stage'], {
                        stage: String(activeProgress.stage),
                        total: '6',
                    })}
                </div>
                <div data-test="last-score">
                    {t['last_score']}：<strong class="text-zinc-900 dark:text-zinc-50"
                        >{activeProgress.last_score === null
                            ? t['no_record']
                            : `${activeProgress.last_score}%`}</strong
                    >
                </div>
            </div>

            {#if (activeProgress.status === 'locked' || activeProgress.status === 'penalty') && activeProgress.next_review_at}
                <div
                    class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-700 dark:text-amber-300"
                >
                    {interpolate(t['review_cooldown'], {
                        time: new Date(activeProgress.next_review_at).toLocaleString(),
                    })}
                </div>
            {/if}

            <div class="mt-6 space-y-3">
                <h2
                    data-test="vocab-preview"
                    class="text-sm font-bold text-zinc-900 dark:text-zinc-50"
                >
                    {t['vocab_preview']}
                </h2>
                {#each vocabularies as vocabulary (vocabulary.id)}
                    <article
                        class="rounded-xl border border-zinc-200 bg-zinc-100 p-4 dark:border-zinc-800 dark:bg-zinc-950"
                    >
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                    <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-50">
                                        {vocabulary.word}
                                    </h3>
                                    <span class="text-sm text-zinc-500 italic dark:text-zinc-400"
                                        >{vocabulary.part_of_speech}</span
                                    >
                                    <span class="text-sm text-zinc-500/70 dark:text-zinc-400/70"
                                        >{vocabulary.pronunciation}</span
                                    >
                                </div>
                                <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                                    {definitionOf(vocabulary)}
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <div data-test={`pronunciation-${vocabulary.id}`} class="contents">
                                    <AudioButton
                                        url={vocabulary.audio_url}
                                        label={t['pronunciation']}
                                    />
                                </div>
                                <div data-test={`sentence-${vocabulary.id}`} class="contents">
                                    <AudioButton
                                        url={vocabulary.sentence_audio_url}
                                        label={t['sentence']}
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-200 pt-3 dark:border-zinc-800">
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-50">
                                {vocabulary.example_sentence}
                            </p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {exampleTranslationOf(vocabulary)}
                            </p>
                        </div>
                    </article>
                {/each}
            </div>
        </div>
    </main>

    <StickyActionBar>
        {#if vocabularies.length < 3}
            <p
                class="rounded-xl bg-zinc-100 p-3 text-center text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
            >
                {t['insufficient_vocab']}
            </p>
        {:else if activeProgress.status === 'locked' || activeProgress.status === 'penalty'}
            <button
                type="button"
                disabled
                class="w-full cursor-not-allowed rounded-xl bg-zinc-100 py-3 text-sm font-semibold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
                >{t['cooldown']}</button
            >
        {:else}
            <!--
                Keyed on the destination so the link is rebuilt when it changes. A guest's stage
                arrives after mount, which flips this from the introduction to the review; the
                client router's link action does not pass an anchor's `href` through to itself, so
                it keeps visiting whichever destination it was built with even though the rendered
                attribute updates. Without the key, a returning guest taps start review and is sent to
                the introduction.
            -->
            {#key quizHref}
                <Link
                    href={quizHref}
                    data-test={activeProgress.stage > 0 ? 'start-review' : 'start-learning'}
                    class="block w-full rounded-xl bg-zinc-900 py-3 text-center text-sm font-semibold text-white dark:bg-zinc-50 dark:text-zinc-950"
                >
                    {activeProgress.stage > 0 ? t['start_review'] : t['start_learning']}
                </Link>
            {/key}
        {/if}
    </StickyActionBar>
</div>
