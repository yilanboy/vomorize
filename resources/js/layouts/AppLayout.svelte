<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHeader from '@/components/AppHeader.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        showFooter = true,
        transparentHeader = false,
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        showFooter?: boolean;
        transparentHeader?: boolean;
        children?: Snippet;
    } = $props();
</script>

<div
    class="bg-background text-foreground flex min-h-screen flex-col transition-colors duration-300"
>
    <AppHeader {breadcrumbs} transparent={transparentHeader} />

    <main class={transparentHeader ? '-mt-16 flex-1' : 'flex-1'}>
        {@render children?.()}
    </main>

    {#if showFooter}
        <AppFooter />
    {/if}

    <Toaster />
</div>
