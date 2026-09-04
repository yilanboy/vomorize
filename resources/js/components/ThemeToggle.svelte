<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import Monitor from '@lucide/svelte/icons/monitor';
    import Moon from '@lucide/svelte/icons/moon';
    import Sun from '@lucide/svelte/icons/sun';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { t } from '@/lib/i18n';
    import { themeState } from '@/lib/theme.svelte';
    import { cn } from '@/lib/utils';
    import type { Appearance } from '@/types';

    let { class: className = '' }: { class?: string } = $props();

    const { appearance, updateAppearance } = themeState();

    const options: { value: Appearance; key: string; icon: typeof Sun }[] = [
        { value: 'light', key: 'ui.theme.light', icon: Sun },
        { value: 'dark', key: 'ui.theme.dark', icon: Moon },
        { value: 'system', key: 'ui.theme.system', icon: Monitor },
    ];
</script>

<DropdownMenu>
    <DropdownMenuTrigger asChild>
        {#snippet children(props)}
            <Button
                variant="ghost"
                size="icon"
                class={cn(
                    'inline-flex size-9 cursor-pointer items-center justify-center rounded-lg text-zinc-500 transition-all duration-200 hover:bg-zinc-200/50 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-200 active:scale-95 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 dark:focus-visible:ring-zinc-700',
                    className,
                )}
                onclick={props.onclick}
                aria-expanded={props['aria-expanded']}
                aria-label={t('ui.theme.toggle')}
            >
                <span class="sr-only">{t('ui.theme.toggle')}</span>
                {#if appearance.value === 'light'}
                    <Sun
                        class="size-5 rotate-0 text-amber-500 transition-transform duration-300 hover:rotate-12"
                    />
                {:else if appearance.value === 'dark'}
                    <Moon
                        class="size-5 rotate-0 text-indigo-500 transition-transform duration-300 hover:-rotate-12 dark:text-indigo-400"
                    />
                {:else}
                    <Monitor
                        class="size-5 text-zinc-600 transition-transform duration-300 dark:text-zinc-300"
                    />
                {/if}
            </Button>
        {/snippet}
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-36">
        {#each options as option (option.value)}
            <DropdownMenuItem asChild>
                {#snippet children(props)}
                    <button
                        type="button"
                        class={cn(
                            props.class,
                            'flex w-full items-center justify-between',
                        )}
                        onclick={() => {
                            updateAppearance(option.value);
                            props.onClick?.();
                        }}
                    >
                        <div class="flex items-center gap-2">
                            <option.icon class="size-4" />
                            <span>{t(option.key)}</span>
                        </div>
                        {#if appearance.value === option.value}
                            <Check class="size-4 text-primary" />
                        {/if}
                    </button>
                {/snippet}
            </DropdownMenuItem>
        {/each}
    </DropdownMenuContent>
</DropdownMenu>
