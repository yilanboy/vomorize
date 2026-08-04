<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppNavbar from '@/components/AppNavbar.svelte';
    import { translations } from '@/lib/locale.svelte';

    let {
        title = '',
        description = '',
        children,
    }: {
        title?: string;
        description?: string;
        children?: Snippet;
    } = $props();

    let t = $derived(translations());
    let translatedTitle = $derived(t[title]);
    let translatedDescription = $derived(t[description]);
</script>

<div class="flex min-h-screen flex-col justify-between bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-50">
    <div>
        <AppNavbar />

        <div class="flex flex-col items-center justify-center px-4 py-12 sm:px-6">
            <div class="w-full max-w-md space-y-6">
                {#if translatedTitle || translatedDescription}
                    <div class="space-y-2 text-center">
                        {#if translatedTitle}
                            <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">
                                {translatedTitle}
                            </h1>
                        {/if}
                        {#if translatedDescription}
                            <p class="text-base text-zinc-500 dark:text-zinc-400">{translatedDescription}</p>
                        {/if}
                    </div>
                {/if}

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                    {@render children?.()}
                </div>
            </div>
        </div>
    </div>
</div>
