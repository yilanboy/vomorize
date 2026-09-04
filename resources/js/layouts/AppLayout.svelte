<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHeader from '@/components/AppHeader.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        showFooter = true,
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        showFooter?: boolean;
        children?: Snippet;
    } = $props();
</script>

<div
    class="flex min-h-screen flex-col bg-background text-foreground transition-colors duration-300"
>
    <AppHeader {breadcrumbs} />

    <main class="flex-1">
        {@render children?.()}
    </main>

    {#if showFooter}
        <AppFooter />
    {/if}

    <Toaster />
</div>
