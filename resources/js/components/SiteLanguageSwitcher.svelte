<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import Languages from '@lucide/svelte/icons/languages';
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
        class: className = '',
    }: {
        class?: string;
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
        }
    }
</script>

<DropdownMenu>
    <DropdownMenuTrigger asChild>
        {#snippet children(props)}
            <button
                type="button"
                class={{
                    'text-foreground/75 inline-flex size-9 cursor-pointer items-center justify-center rounded-full transition-[background-color,color,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] hover:bg-blue-600/8 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none active:scale-95 motion-reduce:transition-none dark:hover:bg-blue-400/12 dark:hover:text-blue-400': true,
                    [className]: Boolean(className),
                }}
                onclick={props.onclick}
                aria-expanded={props['aria-expanded']}
                aria-label={t('ui.sites.switch_site')}
            >
                <span class="sr-only">{t('ui.sites.switch_site')}</span>
                <Languages
                    class="size-5 transition-transform duration-300 hover:scale-105 motion-reduce:transition-none"
                />
            </button>
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
