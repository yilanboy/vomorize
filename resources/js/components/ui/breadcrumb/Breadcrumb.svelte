<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        children,
        class: className = '',
    }: {
        children?: Snippet;
        class?: string;
    } = $props();
</script>

<nav aria-label="breadcrumb" class={cn('text-sm', className)}>
    {@render children?.()}
</nav>
