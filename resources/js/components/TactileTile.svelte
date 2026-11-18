<script lang="ts">
    import type { LinkComponentBaseProps } from '@inertiajs/core';
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    export type TactileTileVariant =
        | 'default'
        | 'muted'
        | 'primary'
        | 'emerald'
        | 'amber';

    const variantClasses: Record<TactileTileVariant, string> = {
        default:
            'border-border bg-card text-foreground hover:border-primary/60 shadow-[0_3px_0_0_rgba(0,0,0,0.12)] dark:border-zinc-700/80 dark:shadow-[0_3px_0_0_rgba(255,255,255,0.1)]',
        muted: 'border-zinc-200/90 bg-card text-muted-foreground hover:border-zinc-300 hover:text-foreground shadow-[0_3px_0_0_rgba(0,0,0,0.09)] dark:border-zinc-800 dark:bg-zinc-900/70 dark:text-zinc-400 dark:shadow-[0_3px_0_0_rgba(255,255,255,0.08)] dark:hover:border-zinc-700 dark:hover:text-zinc-200',
        primary:
            'border-blue-400/80 bg-blue-50/90 text-blue-700 hover:border-blue-500 shadow-[0_3px_0_0_rgba(37,99,235,0.34)] dark:border-blue-500/70 dark:bg-blue-950/50 dark:text-blue-300 dark:shadow-[0_3px_0_0_rgba(59,130,246,0.38)] dark:hover:border-blue-400',
        emerald:
            'border-emerald-300/80 bg-emerald-50/90 text-emerald-800 hover:border-emerald-400 shadow-[0_3px_0_0_rgba(5,150,105,0.26)] dark:border-emerald-700/70 dark:bg-emerald-950/40 dark:text-emerald-300 dark:shadow-[0_3px_0_0_rgba(16,185,129,0.28)] dark:hover:border-emerald-600',
        amber: 'border-amber-300/80 bg-amber-50/85 text-amber-800 hover:border-amber-400 shadow-[0_3px_0_0_rgba(217,119,6,0.26)] dark:border-amber-700/70 dark:bg-amber-950/40 dark:text-amber-300 dark:shadow-[0_3px_0_0_rgba(245,158,11,0.28)] dark:hover:border-amber-600',
    };

    let {
        href,
        variant = 'default',
        depressed = false,
        type = 'button',
        disabled = false,
        class: className = '',
        children,
        ...rest
    }: {
        href?: LinkComponentBaseProps['href'];
        variant?: TactileTileVariant;
        depressed?: boolean;
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
        class?: string;
        children?: Snippet;
        [key: string]: unknown;
    } = $props();

    const tileClasses = $derived(
        cn(
            'relative flex items-center justify-center rounded-xl border transition-all duration-150 select-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:outline-none',
            {
                'cursor-pointer hover:-translate-y-0.5 active:translate-y-[3px] active:shadow-none':
                    !depressed,
                [variantClasses[variant]]: !depressed,
                'border-border/40 bg-muted/20 text-muted-foreground/20 pointer-events-none translate-y-0.5 border-dashed opacity-40 shadow-none':
                    depressed,
            },
            className,
        ),
    );
</script>

{#if href !== undefined}
    <Link {href} class={tileClasses} {...rest}>
        {@render children?.()}
    </Link>
{:else}
    <button {type} {disabled} class={tileClasses} {...rest}>
        {@render children?.()}
    </button>
{/if}
