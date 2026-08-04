<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { cn } from '@/lib/utils';
    import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
    import { SIDEBAR_CONTEXT, type SidebarContext } from './context';

    type Size = 'default' | 'lg';
    type AsChildProps = {
        class?: string;
        [key: string]: any;
    };

    let {
        asChild = false,
        class: className = '',
        isActive = false,
        size = 'default',
        tooltip,
        children,
        ...rest
    }: {
        asChild?: boolean;
        class?: string;
        isActive?: boolean;
        size?: Size;
        tooltip?: string;
        children?: Snippet<[AsChildProps]>;
        [key: string]: unknown;
    } = $props();

    const { isMobile, state } = getContext<SidebarContext>(SIDEBAR_CONTEXT);

    const base =
        'peer/menu-button ring-zinc-400 dark:ring-zinc-600 flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm outline-hidden transition-[width,height,padding] hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-50 focus-visible:ring-2 active:bg-zinc-100 active:text-zinc-900 dark:active:bg-zinc-800 dark:active:text-zinc-50 disabled:pointer-events-none disabled:opacity-50 group-has-data-[sidebar=menu-action]/menu-item:pr-8 aria-disabled:pointer-events-none aria-disabled:opacity-50 data-[active=true]:bg-zinc-100 data-[active=true]:font-medium data-[active=true]:text-zinc-900 dark:data-[active=true]:bg-zinc-800 dark:data-[active=true]:text-zinc-50 data-[state=open]:hover:bg-zinc-100 data-[state=open]:hover:text-zinc-900 dark:data-[state=open]:hover:bg-zinc-800 dark:data-[state=open]:hover:text-zinc-50 group-data-[collapsible=icon]:size-8! group-data-[collapsible=icon]:p-2! [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0';
    const sizeClasses: Record<Size, string> = {
        default: 'h-8 text-sm',
        lg: 'h-12 text-sm group-data-[collapsible=icon]:p-0!',
    };

    const classes = () => {
        const activeClasses = isActive
            ? 'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50'
            : '';
        return cn(base, sizeClasses[size], activeClasses, className);
    };
</script>

{#if tooltip}
    <Tooltip disabled={$state !== 'collapsed' || $isMobile}>
        <TooltipTrigger>
            {#snippet child({ props: triggerProps })}
                {#if asChild}
                    {@render children?.({
                        class: classes(),
                        'data-slot': 'sidebar-menu-button',
                        'data-sidebar': 'menu-button',
                        'data-size': size,
                        'data-active': isActive,
                        ...rest,
                        ...triggerProps,
                    })}
                {:else}
                    <button
                        class={classes()}
                        type="button"
                        data-slot="sidebar-menu-button"
                        data-sidebar="menu-button"
                        data-size={size}
                        data-active={isActive}
                        {...rest}
                        {...triggerProps}
                    >
                        {@render children?.({})}
                    </button>
                {/if}
            {/snippet}
        </TooltipTrigger>
        <TooltipContent side="right" align="center">
            {tooltip}
        </TooltipContent>
    </Tooltip>
{:else}
    {#if asChild}
        {@render children?.({
            class: classes(),
            'data-slot': 'sidebar-menu-button',
            'data-sidebar': 'menu-button',
            'data-size': size,
            'data-active': isActive,
            ...rest,
        })}
    {:else}
        <button
            class={classes()}
            type="button"
            data-slot="sidebar-menu-button"
            data-sidebar="menu-button"
            data-size={size}
            data-active={isActive}
            {...rest}
        >
            {@render children?.({})}
        </button>
    {/if}
{/if}
