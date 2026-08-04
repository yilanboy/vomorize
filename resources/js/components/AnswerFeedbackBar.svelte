<script lang="ts">
    import { fly } from 'svelte/transition';
    import { translations } from '@/lib/locale.svelte';

    let {
        answered = false,
        correct = false,
        correctAnswer,
    } = $props<{
        answered?: boolean;
        correct?: boolean;
        correctAnswer: string;
    }>();

    let t = $derived(translations());
    let message = $derived(
        correct
            ? t['correct']
            : t['quiz_wrong_answer'].replace(
                  ':answer',
                  correctAnswer,
              ),
    );
</script>

<!--
    The live region is rendered unconditionally and only its text is swapped in. A region
    that is introduced at the same moment its content arrives is not announced by iOS
    VoiceOver, nor by several screen-reader-and-browser combinations, because the region
    was not in the accessibility tree beforehand.

    Polite rather than assertive: a wrong answer is not an error condition, and assertive
    announcements interrupt whatever else is being read. Atomic so the verdict and the
    correct answer are spoken as one utterance.
-->
<div role="status" aria-live="polite" aria-atomic="true">
    {#if answered}
        <p
            transition:fly={{ y: 16, duration: 220 }}
            class="mb-3 w-full rounded-xl border p-3 text-sm font-medium {correct
                ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                : 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300'}"
        >
            {message}
        </p>
    {/if}
</div>
