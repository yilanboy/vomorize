<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Award from '@lucide/svelte/icons/award';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Volume2 from '@lucide/svelte/icons/volume-2';
    import XCircle from '@lucide/svelte/icons/x-circle';
    import { audioPlayer } from '@/lib/audioPlayer.svelte';
    import { t } from '@/lib/i18n';
    import type { MistakeReviewItem } from '@/types/quiz';

    let {
        score,
        passed,
        groupId,
        levelId,
        mistakes = [],
        onRetry,
        onDone,
    }: {
        score: number;
        passed: boolean;
        groupId: number;
        levelId?: number;
        mistakes?: MistakeReviewItem[];
        onRetry: () => void;
        onDone: () => void;
    } = $props();

    function playAudio(url: string, id: string) {
        if (url) {
            void audioPlayer.play(url, id);
        }
    }
</script>

<div class="mx-auto flex w-full max-w-xl flex-col gap-6 pb-12">
    <!-- Score & Status Card -->
    <div
        class="bg-card flex flex-col items-center gap-5 rounded-2xl border p-6 text-center shadow-sm sm:p-8 {passed
            ? 'border-emerald-500/30 bg-emerald-500/5 dark:bg-emerald-950/20'
            : 'border-amber-500/30 bg-amber-500/5 dark:bg-amber-950/20'}"
    >
        <div
            class="flex h-16 w-16 items-center justify-center rounded-2xl {passed
                ? 'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400'
                : 'bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400'}"
        >
            {#if passed}
                <Award class="h-8 w-8" />
            {:else}
                <XCircle class="h-8 w-8" />
            {/if}
        </div>

        <div class="flex flex-col gap-1.5">
            <h2 class="text-foreground text-2xl font-bold tracking-tight">
                {passed
                    ? t('ui.quiz.result.title_passed')
                    : t('ui.quiz.result.title_failed')}
            </h2>
            <p class="text-muted-foreground text-sm font-medium">
                {t('ui.quiz.result.pass_threshold')}
            </p>
        </div>

        <div class="flex items-baseline gap-2">
            <span
                class="text-5xl font-extrabold tracking-tight {passed
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-amber-600 dark:text-amber-400'}"
            >
                {score}
            </span>
            <span class="text-muted-foreground text-xl font-semibold"
                >/ 100</span
            >
        </div>

        <!-- Cooldown / Retry Notice Banner -->
        <div
            class="rounded-xl border p-4 text-left text-sm leading-relaxed font-medium {passed
                ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-900 dark:text-emerald-200'
                : 'border-amber-500/30 bg-amber-500/10 text-amber-900 dark:text-amber-200'}"
        >
            {passed
                ? t('ui.quiz.result.cooldown_notice')
                : t('ui.quiz.result.failed_notice')}
        </div>
    </div>

    <!-- Mistakes Review Section -->
    {#if mistakes.length > 0}
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h3 class="text-foreground text-lg font-bold">
                    {t('ui.quiz.result.mistakes_title', {
                        count: mistakes.length,
                    })}
                </h3>
            </div>

            <div class="flex flex-col gap-3">
                {#each mistakes as mistake (mistake.id)}
                    <div
                        class="border-border/70 bg-card flex flex-col gap-3 rounded-xl border p-4 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-baseline gap-2.5">
                                <span class="text-foreground text-lg font-bold">
                                    {mistake.word}
                                </span>
                                <span
                                    class="text-muted-foreground font-mono text-sm"
                                >
                                    {mistake.pronunciation}
                                </span>
                                <span
                                    class="bg-secondary text-secondary-foreground inline-flex items-center rounded-md px-2 py-0.5 text-sm font-medium"
                                >
                                    {mistake.part_of_speech}
                                </span>
                            </div>

                            {#if mistake.word_audio_url}
                                <button
                                    type="button"
                                    class="hover:bg-muted text-foreground border-border/70 flex h-8 w-8 items-center justify-center rounded-lg border transition-colors"
                                    onclick={() =>
                                        playAudio(
                                            mistake.word_audio_url,
                                            `mistake-${mistake.id}`,
                                        )}
                                    title={t('ui.intro.card.play_word')}
                                >
                                    <Volume2
                                        class="h-4 w-4 {audioPlayer.isPlaying(
                                            `mistake-${mistake.id}`,
                                        )
                                            ? 'text-primary animate-pulse'
                                            : 'text-muted-foreground'}"
                                    />
                                </button>
                            {/if}
                        </div>

                        <p class="text-foreground text-base font-semibold">
                            {mistake.definition}
                        </p>

                        {#if mistake.example_sentence}
                            <div class="bg-muted/40 rounded-lg p-2.5">
                                <p class="text-foreground text-sm font-medium">
                                    {mistake.example_sentence}
                                </p>
                                {#if mistake.example_translation}
                                    <p
                                        class="text-muted-foreground mt-0.5 text-sm"
                                    >
                                        {mistake.example_translation}
                                    </p>
                                {/if}
                            </div>
                        {/if}
                    </div>
                {/each}
            </div>
        </div>
    {:else}
        <div
            class="bg-card border-border/70 flex items-center gap-3 rounded-xl border p-4 shadow-sm"
        >
            <CheckCircle2
                class="h-6 w-6 shrink-0 text-emerald-600 dark:text-emerald-400"
            />
            <p class="text-foreground text-sm font-medium">
                {t('ui.quiz.result.all_correct')}
            </p>
        </div>
    {/if}

    <!-- Action Buttons -->
    <div class="flex flex-col gap-3 pt-2">
        {#if passed}
            <button
                type="button"
                class="bg-primary hover:bg-primary/90 text-primary-foreground flex min-h-[50px] w-full items-center justify-center gap-2 rounded-xl text-base font-semibold shadow-sm transition-all active:scale-[0.99]"
                onclick={onDone}
            >
                {t('ui.quiz.result.btn_done')}
            </button>

            <Link
                href="/dashboard"
                class="hover:bg-muted text-foreground border-border/80 flex min-h-[46px] w-full items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
            >
                {t('ui.quiz.result.btn_dashboard')}
            </Link>
        {:else}
            <button
                type="button"
                class="bg-primary hover:bg-primary/90 text-primary-foreground flex min-h-[50px] w-full items-center justify-center gap-2 rounded-xl text-base font-semibold shadow-sm transition-all active:scale-[0.99]"
                onclick={onRetry}
            >
                <RotateCcw class="h-4 w-4" />
                <span>{t('ui.quiz.result.btn_retry')}</span>
            </button>

            <button
                type="button"
                class="hover:bg-muted text-foreground border-border/80 flex min-h-[46px] w-full items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                onclick={onDone}
            >
                {t('ui.quiz.result.btn_done')}
            </button>
        {/if}
    </div>
</div>
