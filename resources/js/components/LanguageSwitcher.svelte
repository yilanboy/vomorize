<script lang="ts">
    import Languages from '@lucide/svelte/icons/languages';
    import Check from '@lucide/svelte/icons/check';
    import { router } from '@inertiajs/svelte';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { Button } from '@/components/ui/button';

    let {
        currentLocale = 'zh_TW',
        availableLocales = { zh_TW: '繁體中文', zh_CN: '简体中文', ja: '日本語' },
    } = $props<{
        currentLocale?: string;
        availableLocales?: Record<string, string>;
    }>();

    function selectLocale(code: string) {
        if (code === currentLocale) {
            return;
        }

        const targetRouteKey = code.toLowerCase().replace('_', '-');
        if (typeof document !== 'undefined') {
            document.cookie = `locale=${targetRouteKey};path=/;max-age=31536000;SameSite=Lax`;
        }
        router.visit(`/${targetRouteKey}`);
    }
</script>

<DropdownMenu>
    <DropdownMenuTrigger asChild>
        {#snippet children(props)}
            <Button
                variant="ghost"
                size="icon"
                class="relative h-9 w-9 rounded-lg text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
                aria-label="Select language"
                data-test="language-switcher"
                onclick={props.onclick}
                aria-expanded={props['aria-expanded']}
                data-state={props['data-state']}
            >
                <Languages class="h-5 w-5" />
            </Button>
        {/snippet}
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-40">
        {#each Object.entries(availableLocales) as [code, name]}
            <DropdownMenuItem
                onclick={() => selectLocale(code)}
                class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm font-medium"
            >
                <span>{name}</span>
                {#if code === currentLocale}
                    <Check class="h-4 w-4 text-zinc-900 dark:text-zinc-50" />
                {/if}
            </DropdownMenuItem>
        {/each}
    </DropdownMenuContent>
</DropdownMenu>
