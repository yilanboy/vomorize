<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
        children,
    }: {
        class?: string;
        children?: Snippet;
    } = $props();
</script>

<div
    data-slot="card"
    class={cn(
        'flex flex-col gap-6 rounded-xl border bg-zinc-50 py-6 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-50 shadow-sm',
        className,
    )}
>
    {@render children?.()}
</div>
