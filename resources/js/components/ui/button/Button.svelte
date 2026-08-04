<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type Variant = 'default' | 'secondary' | 'ghost' | 'destructive' | 'outline' | 'link';
    type Size = 'default' | 'sm' | 'lg' | 'icon';
    type AsChildProps = {
        class?: string;
        onClick?: (event: MouseEvent) => void;
        [key: string]: any;
    };

    const base =
        'inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 dark:focus-visible:ring-zinc-600 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-100 dark:focus-visible:ring-offset-zinc-950 disabled:pointer-events-none disabled:opacity-50';

    const variants: Record<Variant, string> = {
        default: 'bg-zinc-900 text-white dark:bg-zinc-50 dark:text-zinc-950 shadow hover:bg-zinc-800 dark:hover:bg-zinc-200',
        secondary: 'bg-zinc-200 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50 shadow-sm hover:bg-zinc-300 dark:hover:bg-zinc-700',
        ghost: 'hover:bg-zinc-200 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-50',
        destructive: 'bg-red-600 text-white dark:bg-red-500 shadow hover:bg-red-700 dark:hover:bg-red-600',
        outline: 'border border-zinc-300 bg-white dark:border-zinc-700 dark:bg-zinc-800 hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-700 dark:hover:text-zinc-50',
        link: 'text-zinc-900 dark:text-zinc-50 underline-offset-4 hover:underline',
    };

    const sizes: Record<Size, string> = {
        default: 'h-9 px-4 py-2',
        sm: 'h-8 rounded-md px-3 text-sm',
        lg: 'h-10 rounded-md px-8',
        icon: 'h-9 w-9',
    };

    let {
        children,
        asChild = false,
        variant = 'default',
        size = 'default',
        class: className = '',
        type = 'button',
        ...rest
    }: {
        children?: Snippet<[AsChildProps]>;
        asChild?: boolean;
        variant?: Variant;
        size?: Size;
        class?: string;
        type?: 'button' | 'submit' | 'reset';
        [key: string]: unknown;
    } = $props();

    const classes = () => cn(base, variants[variant], sizes[size], className);
</script>

{#if asChild}
    {@render children?.({ class: classes(), ...rest })}
{:else}
    <button class={classes()} {type} {...rest}>
        {@render children?.({})}
    </button>
{/if}
