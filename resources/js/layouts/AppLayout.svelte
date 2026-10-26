<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHeader from '@/components/AppHeader.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        showHeader = true,
        showFooter = true,
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        showHeader?: boolean;
        showFooter?: boolean;
        children?: Snippet;
    } = $props();
</script>

<div
    class="bg-background text-foreground flex min-h-screen flex-col transition-colors duration-300"
>
    {#if showHeader}
        <AppHeader {breadcrumbs} />
    {/if}

    <main class="flex-1">
        {@render children?.()}
    </main>

    {#if showFooter}
        <AppFooter />
    {/if}

    <Toaster />
</div>
