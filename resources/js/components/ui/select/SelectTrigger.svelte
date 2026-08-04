<script lang="ts">
    import { Select as SelectPrimitive } from 'bits-ui';
    import ChevronDownIcon from '@lucide/svelte/icons/chevron-down';
    import { cn } from '@/lib/utils';
    import type { ComponentProps } from 'svelte';

    type TriggerProps = ComponentProps<typeof SelectPrimitive.Trigger> & {
        size?: 'sm' | 'default';
    };

    let {
        ref = $bindable(null),
        class: className,
        children,
        size = 'default',
        ...restProps
    }: TriggerProps = $props();
</script>

<SelectPrimitive.Trigger
    bind:ref
    data-slot="select-trigger"
    data-size={size}
    class={cn(
        "flex w-fit items-center justify-between gap-2 rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none select-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-50 focus-visible:border-zinc-400 dark:focus-visible:border-zinc-600 focus-visible:ring-[3px] focus-visible:ring-zinc-400/50 dark:focus-visible:ring-zinc-600/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-red-600 dark:aria-invalid:border-red-400 aria-invalid:ring-red-600/20 dark:aria-invalid:ring-red-400/40 data-[placeholder]:text-zinc-500 dark:data-[placeholder]:text-zinc-400 data-[size=default]:h-9 data-[size=sm]:h-8 *:data-[slot=select-value]:line-clamp-1 *:data-[slot=select-value]:flex *:data-[slot=select-value]:items-center *:data-[slot=select-value]:gap-2 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4 [&_svg:not([class*='text-'])]:text-zinc-500 dark:[&_svg:not([class*='text-'])]:text-zinc-400",
        className,
    )}
    {...restProps}
>
    {@render children?.()}
    <ChevronDownIcon class="size-4 opacity-50" />
</SelectPrimitive.Trigger>
