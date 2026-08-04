<script lang="ts">
    import { onMount } from 'svelte';
    import { playAudio, subscribeAudio } from '@/lib/audio';
    import { translations } from '@/lib/locale.svelte';
    import type { AudioState } from '@/lib/audio';

    let {
        url,
        label = '',
        size = 'sm',
    } = $props<{
        url: string;
        label?: string;
        size?: 'sm' | 'lg';
    }>();

    let audioState = $state<AudioState>('idle');
    let t = $derived(translations());
    let isUnavailable = $state(false);
    let isBusy = $derived(audioState === 'loading' || audioState === 'playing');

    /**
     * At the large size the button is the question rather than an aid offered beside one, so it
     * carries no text label — the icon fills it and the `aria-label` speaks for it — and its
     * unavailable notice stacks underneath instead of beside it, which would push the circle off
     * the centre line it is placed on.
     */
    let isLarge = $derived(size === 'lg');

    onMount(() => subscribeAudio(url, (state) => (audioState = state)));

    async function handleClick() {
        if (!url || isBusy) {
            return;
        }

        isUnavailable = false;

        const success = await playAudio(url);

        if (!success) {
            isUnavailable = true;
            setTimeout(() => {
                isUnavailable = false;
            }, 3000);
        }
    }
</script>

<div
    class={{
        'inline-flex items-center': true,
        'flex-col space-y-2': isLarge,
        'space-x-2': !isLarge,
    }}
>
    <button
        type="button"
        onclick={handleClick}
        disabled={isBusy}
        aria-label={label || t['pronunciation']}
        data-audio-url={url}
        class={{
            'inline-flex items-center border border-zinc-200 bg-white text-zinc-900 shadow-xs hover:bg-zinc-25 hover:text-zinc-900 focus:outline-hidden disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-50 dark:hover:bg-zinc-700 dark:hover:text-zinc-50': true,
            'h-20 w-20 justify-center rounded-full': isLarge,
            'space-x-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium': !isLarge,
        }}
    >
        {#if isBusy}
            <span
                class={{
                    'animate-spin rounded-full border-zinc-500/30 border-t-zinc-500 dark:border-zinc-400/30 dark:border-t-zinc-400': true,
                    'h-8 w-8 border-[3px]': isLarge,
                    'h-4 w-4 border-2': !isLarge,
                }}
                aria-hidden="true"
            ></span>
        {:else}
            <svg
                class={{
                    'h-8 w-8 text-zinc-900 dark:text-zinc-50': isLarge,
                    'h-4 w-4 text-zinc-500 dark:text-zinc-400': !isLarge,
                }}
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"
                />
            </svg>
        {/if}

        {#if label && !isLarge}
            <span>{label}</span>
        {/if}
    </button>

    {#if isUnavailable}
        <span class="text-sm font-medium text-amber-600">{t['audio_unavailable']}</span>
    {/if}
</div>
