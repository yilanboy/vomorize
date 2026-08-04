<script lang="ts">
    import type { Snippet } from 'svelte';

    let { children }: { children?: Snippet } = $props();
</script>

<div
    class="sticky bottom-0 border-t border-zinc-200 bg-zinc-100/90 pt-4 pb-[max(1rem,env(safe-area-inset-bottom))] backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/90"
>
    <div data-test="action-bar" class="mx-auto max-w-2xl px-4 sm:px-6">
        {@render children?.()}
    </div>
</div>
