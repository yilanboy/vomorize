<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type Variant = 'default' | 'secondary' | 'destructive' | 'outline';

    const base =
        'inline-flex items-center justify-center rounded-full border px-2 py-0.5 text-sm font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-zinc-400 dark:focus-visible:border-zinc-600 focus-visible:ring-zinc-400/50 dark:focus-visible:ring-zinc-600/50 focus-visible:ring-[3px] aria-invalid:ring-red-600/20 dark:aria-invalid:ring-red-400/40 aria-invalid:border-red-600 dark:aria-invalid:border-red-400 transition-[color,box-shadow] overflow-hidden';

    const variantClasses: Record<Variant, string> = {
        default: 'border-transparent bg-zinc-900 text-white dark:bg-zinc-50 dark:text-zinc-950 [a&]:hover:bg-zinc-800 dark:[a&]:hover:bg-zinc-200',
        secondary:
            'border-transparent bg-zinc-200 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50 [a&]:hover:bg-zinc-300 dark:[a&]:hover:bg-zinc-700',
        destructive:
            'border-transparent bg-red-600 text-white dark:bg-red-500 [a&]:hover:bg-red-700 dark:[a&]:hover:bg-red-600 focus-visible:ring-red-600/20 dark:focus-visible:ring-red-400/40',
        outline: 'text-zinc-900 dark:text-zinc-50 [a&]:hover:bg-zinc-200 dark:[a&]:hover:bg-zinc-800 [a&]:hover:text-zinc-900 dark:[a&]:hover:text-zinc-50',
    };

    let {
        variant = 'default',
        class: className = '',
        children,
        ...rest
    }: {
        variant?: Variant;
        class?: string;
        children?: Snippet;
        [key: string]: unknown;
    } = $props();
</script>

<div data-slot="badge" class={cn(base, variantClasses[variant], className)} {...rest}>
    {@render children?.()}
</div>
