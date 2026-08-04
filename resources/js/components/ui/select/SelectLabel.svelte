<script lang="ts">
    import { cn } from '@/lib/utils';
    import type { HTMLAttributes } from 'svelte/elements';

    let {
        ref = $bindable(null),
        class: className,
        children,
        ...restProps
    }: HTMLAttributes<HTMLDivElement> & {
        ref?: HTMLDivElement | null;
        children?: () => unknown;
    } = $props();
</script>

<div
    bind:this={ref}
    data-slot="select-label"
    class={cn('px-2 py-1.5 text-sm text-zinc-500 dark:text-zinc-400', className)}
    {...restProps}
>
    {@render children?.()}
</div>
