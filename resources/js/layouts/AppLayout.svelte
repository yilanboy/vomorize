<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHeader from '@/components/AppHeader.svelte';
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        showFooter = true,
        headerOverlay = false,
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        showFooter?: boolean;
        headerOverlay?: boolean;
        children?: Snippet;
    } = $props();
</script>

<div
    class="bg-background text-foreground flex min-h-screen flex-col transition-colors duration-300"
>
    <AppHeader />

    {#if breadcrumbs.length > 0}
        <div class="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            <Breadcrumbs {breadcrumbs} />
        </div>
    {/if}

    <main class={{ 'flex-1': true, '-mt-16': headerOverlay }}>
        {@render children?.()}
    </main>

    {#if showFooter}
        <AppFooter />
    {/if}

    <Toaster />
</div>
