<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppNavbar from '@/components/AppNavbar.svelte';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();
</script>

<div class="flex min-h-dvh flex-col bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-50">
    <AppNavbar />
    {@render children?.()}
</div>
