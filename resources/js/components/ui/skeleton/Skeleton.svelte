<script lang="ts">
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
    }: {
        class?: string;
    } = $props();
</script>

<div data-slot="skeleton" class={cn('animate-pulse rounded-md bg-zinc-900/10 dark:bg-zinc-50/10', className)}></div>
