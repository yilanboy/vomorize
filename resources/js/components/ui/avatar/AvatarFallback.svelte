<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
        children,
        ...rest
    }: { class?: string; children?: Snippet } = $props();
</script>

<div
    class={cn(
        'flex h-full w-full items-center justify-center rounded-full bg-zinc-100 text-sm font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
        className,
    )}
    {...rest}
>
    {@render children?.()}
</div>
