<script lang="ts">
    import type { Snippet } from 'svelte';
    import { Toaster } from 'svelte-sonner';
    import AppNavbar from '@/components/AppNavbar.svelte';
    import { currentLocale} from '@/lib/locale.svelte';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();
</script>

<div class="flex min-h-dvh flex-col bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-50">
    {#key currentLocale()}
        <AppNavbar />
    {/key}

    {@render children?.()}
</div>

<Toaster />
