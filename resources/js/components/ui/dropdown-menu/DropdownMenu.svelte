<script lang="ts">
    import type { Snippet } from 'svelte';
    import { setContext } from 'svelte';
    import { cn } from '@/lib/utils';
    import { DROPDOWN_MENU_CONTEXT, type DropdownMenuContext } from './context';

    let {
        open = $bindable(false),
        class: className = '',
        children,
    }: {
        open?: boolean;
        class?: string;
        children?: Snippet;
    } = $props();
    let menuElement: HTMLDivElement | null = null;

    const context: DropdownMenuContext = {
        open: () => open,
        setOpen: (value: boolean) => {
            open = value;
        },
    };

    setContext(DROPDOWN_MENU_CONTEXT, context);

    const handlePointerDown = (event: PointerEvent) => {
        if (!open || !menuElement) return;

        const target = event.target as Node | null;
        if (target && !menuElement.contains(target)) {
            open = false;
        }
    };
</script>

<svelte:document onpointerdown={handlePointerDown} />

<div class={cn('relative', className)} bind:this={menuElement}>
    {@render children?.()}
</div>
