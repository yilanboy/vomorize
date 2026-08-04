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

<p data-slot="card-description" class={cn('text-sm text-zinc-500 dark:text-zinc-400', className)}>
    {@render children?.()}
</p>
