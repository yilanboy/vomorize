<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import Languages from '@lucide/svelte/icons/languages';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { t } from '@/lib/i18n';
    import {
        isSupportedLocale,
        setLocale,
        type SupportedLocale,
    } from '@/lib/locale';
    import { cn } from '@/lib/utils';

    interface SiteOption {
        id: SupportedLocale;
        labelKey: string;
    }

    let {
        variant = 'desktop',
        class: className = '',
        onSelect,
    }: {
        variant?: 'desktop' | 'mobile';
        class?: string;
        onSelect?: () => void;
    } = $props();

    const currentLocale = $derived<SupportedLocale>(
        isSupportedLocale(page.props.locale) ? page.props.locale : 'zh_TW',
    );

    const sites: SiteOption[] = [
        {
            id: 'zh_TW',
            labelKey: 'ui.sites.zh_TW',
        },
        {
            id: 'ja',
            labelKey: 'ui.sites.ja',
        },
        {
            id: 'zh_CN',
            labelKey: 'ui.sites.zh_CN',
        },
    ];

    function handleSwitch(locale: SupportedLocale) {
        if (locale !== currentLocale) {
            setLocale(locale);
            onSelect?.();
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
                    <Languages
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
                                handleSwitch(site.id);
                            }}
                        >
                            <span>{t(site.labelKey)}</span>
                            {#if site.id === currentLocale}
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
            <Languages class="size-4" />
            <span>{t('ui.sites.switch_site')}</span>
        </div>
        <div class="grid grid-cols-1 gap-1.5">
            {#each sites as site (site.id)}
                <button
                    type="button"
                    class={{
                        'flex min-h-11 w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-colors': true,
                        'bg-accent text-foreground font-semibold':
                            site.id === currentLocale,
                        'text-muted-foreground hover:bg-accent/60 hover:text-foreground':
                            site.id !== currentLocale,
                    }}
                    onclick={() => handleSwitch(site.id)}
                >
                    <span>{t(site.labelKey)}</span>
                    {#if site.id === currentLocale}
                        <Check class="text-primary size-4" />
                    {/if}
                </button>
            {/each}
        </div>
    </div>
{/if}
