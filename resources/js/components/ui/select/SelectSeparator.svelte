<script lang="ts">
    import { Separator } from '@/components/ui/separator';
    import { cn } from '@/lib/utils';
    import type { ComponentProps } from 'svelte';

    let { class: className, ...restProps }: ComponentProps<typeof Separator> = $props();
</script>

<Separator
    data-slot="select-separator"
    class={cn('pointer-events-none -mx-1 my-1 h-px bg-zinc-200 dark:bg-zinc-800', className)}
    {...restProps}
/>
