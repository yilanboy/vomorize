<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import Globe from '@lucide/svelte/icons/globe';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { t } from '@/lib/i18n';
    import { cn } from '@/lib/utils';

    interface SiteOption {
        id: 'zh_TW' | 'ja' | 'zh_CN';
        labelKey: string;
        url: string;
        isCurrent: boolean;
    }

    let {
        variant = 'desktop',
        class: className = '',
    }: {
        variant?: 'desktop' | 'mobile';
        class?: string;
    } = $props();

    const sites: SiteOption[] = [
        {
            id: 'zh_TW',
            labelKey: 'ui.sites.zh_TW',
            url: 'https://vomorize.com',
            isCurrent: true,
        },
        {
            id: 'ja',
            labelKey: 'ui.sites.ja',
            url: 'https://ja.vomorize.com',
            isCurrent: false,
        },
        {
            id: 'zh_CN',
            labelKey: 'ui.sites.zh_CN',
            url: 'https://cn.vomorize.com',
            isCurrent: false,
        },
    ];

    function handleSwitch(site: SiteOption) {
        if (!site.isCurrent) {
            window.location.href = site.url;
        }
    }
</script>

{#if variant === 'desktop'}
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
                    aria-label={t('ui.sites.switch_site')}
                >
                    <span class="sr-only">{t('ui.sites.switch_site')}</span>
                    <Globe
                        class="size-5 transition-transform duration-300 hover:scale-105"
                    />
                </Button>
            {/snippet}
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44">
            {#each sites as site (site.id)}
                <DropdownMenuItem asChild>
                    {#snippet children(props)}
                        <button
                            type="button"
                            class={cn(
                                props.class,
                                'flex w-full cursor-pointer items-center justify-between font-medium',
                            )}
                            onclick={() => {
                                props.onClick?.();
                                handleSwitch(site);
                            }}
                        >
                            <span>{t(site.labelKey)}</span>
                            {#if site.isCurrent}
                                <Check class="text-primary size-4" />
                            {/if}
                        </button>
                    {/snippet}
                </DropdownMenuItem>
            {/each}
        </DropdownMenuContent>
    </DropdownMenu>
{:else}
    <!-- Mobile Variant -->
    <div class={cn('border-border space-y-2 border-t pt-4', className)}>
        <div
            class="text-muted-foreground flex items-center gap-2 px-1 text-sm font-semibold tracking-wider uppercase"
        >
            <Globe class="size-4" />
            <span>{t('ui.sites.switch_site')}</span>
        </div>
        <div class="grid grid-cols-1 gap-1.5">
            {#each sites as site (site.id)}
                <button
                    type="button"
                    class={{
                        'flex min-h-[44px] w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-colors': true,
                        'bg-accent text-foreground font-semibold':
                            site.isCurrent,
                        'text-muted-foreground hover:bg-accent/60 hover:text-foreground':
                            !site.isCurrent,
                    }}
                    onclick={() => handleSwitch(site)}
                >
                    <span>{t(site.labelKey)}</span>
                    {#if site.isCurrent}
                        <Check class="text-primary size-4" />
                    {/if}
                </button>
            {/each}
        </div>
    </div>
{/if}
