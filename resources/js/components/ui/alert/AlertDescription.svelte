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
    data-slot="alert-description"
    class={cn(
        'col-start-2 grid justify-items-start gap-1 text-sm text-zinc-500 dark:text-zinc-400 [&_p]:leading-relaxed',
        className,
    )}
>
    {@render children?.()}
</div>
