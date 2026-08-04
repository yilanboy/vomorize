<script lang="ts">
    import Moon from '@lucide/svelte/icons/moon';
    import Sun from '@lucide/svelte/icons/sun';
    import { Button } from '@/components/ui/button';
    import { translations } from '@/lib/locale.svelte';
    import { themeState } from '@/lib/theme.svelte';

    const { resolvedAppearance, updateAppearance } = themeState();

    let t = $derived(translations());

    /**
     * The icon names the theme a click will move to, rather than the current one, so the
     * button reads as a promise of what it does. A visitor still on the system default
     * therefore sees whichever icon matches the theme their device resolved to.
     */
    let isDark = $derived(resolvedAppearance() === 'dark');
    let label = $derived(
        isDark
            ? t['switch_to_light_theme']
            : t['switch_to_dark_theme'],
    );

    /**
     * Writing the resolved opposite rather than flipping the stored value means a visitor
     * still on the system default gets the theme the icon promised, instead of a first
     * click that appears to do nothing.
     */
    function toggleAppearance() {
        updateAppearance(isDark ? 'light' : 'dark');
    }
</script>

<Button
    variant="ghost"
    size="icon"
    class="relative h-9 w-9 rounded-lg text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
    aria-label={label}
    title={label}
    data-test="theme-toggle"
    onclick={toggleAppearance}
>
    {#if isDark}
        <Sun class="h-5 w-5" />
    {:else}
        <Moon class="h-5 w-5" />
    {/if}
</Button>
