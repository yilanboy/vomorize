<script lang="ts">
    import { getAudioState, playAudio } from '@/lib/audio.svelte';
    import { translations } from '@/lib/locale.svelte';

    let { url, label = '' } = $props<{
        url: string;
        label?: string;
    }>();

    let audioState = $derived(getAudioState(url));
    let t = $derived(translations());
    let isUnavailable = $state(false);
    let isBusy = $derived(audioState === 'loading' || audioState === 'playing');

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

<div class="inline-flex items-center space-x-2">
    <button
        type="button"
        onclick={handleClick}
        disabled={isBusy}
        aria-label={label || t['pronunciation']}
        data-audio-url={url}
        class="hover:bg-zinc-25 inline-flex cursor-pointer items-center space-x-1.5 rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-sm font-medium text-zinc-900 shadow-xs hover:text-zinc-900 focus:outline-hidden disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-50 dark:hover:bg-zinc-700 dark:hover:text-zinc-50"
    >
        {#if isBusy}
            <span
                class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-500/30 border-t-zinc-500 dark:border-zinc-400/30 dark:border-t-zinc-400"
                aria-hidden="true"
            ></span>
        {:else}
            <svg
                class="h-4 w-4 text-zinc-500 dark:text-zinc-400"
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

        {#if label}
            <span>{label}</span>
        {/if}
    </button>

    {#if isUnavailable}
        <span class="text-sm font-medium text-amber-600"
            >{t['audio_unavailable']}</span
        >
    {/if}
</div>
