<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '', orientation = 'horizontal', ...rest } = $props();

    const base = $derived(
        orientation === 'vertical'
            ? 'h-full w-px bg-zinc-200 dark:bg-zinc-800'
            : 'h-px w-full bg-zinc-200 dark:bg-zinc-800',
    );
</script>

<div class={cn('shrink-0', base, className)} {...rest}></div>
