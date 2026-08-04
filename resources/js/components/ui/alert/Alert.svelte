<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type Variant = 'default' | 'destructive';

    const base =
        'relative w-full rounded-lg border px-4 py-3 text-sm grid has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] grid-cols-[0_1fr] has-[>svg]:gap-x-3 gap-y-0.5 items-start [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current';

    const variantClasses: Record<Variant, string> = {
        default: 'bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-50',
        destructive:
            'text-red-600 bg-zinc-50 dark:bg-zinc-900 dark:text-red-400 [&>svg]:text-current *:data-[slot=alert-description]:text-red-600/90 dark:*:data-[slot=alert-description]:text-red-400/90',
    };

    let {
        variant = 'default',
        class: className = '',
        children,
    }: {
        variant?: Variant;
        class?: string;
        children?: Snippet;
    } = $props();
</script>

<div data-slot="alert" class={cn(base, variantClasses[variant], className)} role="alert">
    {@render children?.()}
</div>
