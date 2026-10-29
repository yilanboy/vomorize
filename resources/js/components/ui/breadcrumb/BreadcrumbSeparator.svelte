<script lang="ts">
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
    }: {
        class?: string;
    } = $props();
</script>

<li
    role="presentation"
    aria-hidden="true"
    class={cn('text-muted-foreground/60 inline-flex items-center', className)}
>
    <ChevronRight class="size-3.5" />
</li>

