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

<!--
    The large audio button is used when the audio itself serves as the question prompt
    rather than an aid beside text. It carries no text label — the icon fills the circular
    button and aria-label provides accessibility. The unavailable notice stacks underneath
    to keep the circle centred on the page.
-->
<div class="inline-flex flex-col items-center space-y-2">
    <button
        type="button"
        onclick={handleClick}
        disabled={isBusy}
        aria-label={label || t['pronunciation']}
        data-audio-url={url}
        class="hover:bg-zinc-25 inline-flex h-20 w-20 cursor-pointer items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-900 shadow-xs hover:text-zinc-900 focus:outline-hidden disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-50 dark:hover:bg-zinc-700 dark:hover:text-zinc-50"
    >
        {#if isBusy}
            <span
                class="h-8 w-8 animate-spin rounded-full border-[3px] border-zinc-500/30 border-t-zinc-500 dark:border-zinc-400/30 dark:border-t-zinc-400"
                aria-hidden="true"
            ></span>
        {:else}
            <svg
                class="h-8 w-8 text-zinc-900 dark:text-zinc-50"
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
    </button>

    {#if isUnavailable}
        <span class="text-sm font-medium text-amber-600"
            >{t['audio_unavailable']}</span
        >
    {/if}
</div>
