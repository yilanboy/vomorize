<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { Separator } from '@/components/ui/separator';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { t } from '@/lib/i18n';
    import { toUrl } from '@/lib/utils';
    import { edit as editAppearance } from '@/routes/appearance';
    import { edit as editProfile } from '@/routes/profile';
    import { edit as editSecurity } from '@/routes/security';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const sidebarNavItems: NavItem[] = $derived([
        {
            title: t('ui.settings.layout.nav.profile'),
            href: editProfile(),
        },
        {
            title: t('ui.settings.layout.nav.security'),
            href: editSecurity(),
        },
        {
            title: t('ui.settings.layout.nav.appearance'),
            href: editAppearance(),
        },
    ]);

    const url = currentUrlState();
</script>

<div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <Heading
        title={t('ui.settings.layout.title')}
        description={t('ui.settings.layout.subtitle')}
    />

    <div class="flex flex-col lg:flex-row lg:space-x-12">
        <aside class="w-full max-w-xl lg:w-48">
            <nav
                class="flex flex-col space-y-1 space-x-0"
                aria-label={t('ui.settings.layout.title')}
            >
                {#each sidebarNavItems as item (toUrl(item.href))}
                    <Button
                        variant="ghost"
                        class="w-full justify-start {url.isCurrentUrl(
                            item.href,
                            url.currentUrl,
                        )
                            ? 'bg-muted'
                            : ''}"
                        asChild
                    >
                        {#snippet children(props)}
                            <Link href={toUrl(item.href)} class={props.class}>
                                {item.title}
                            </Link>
                        {/snippet}
                    </Button>
                {/each}
            </nav>
        </aside>

        <Separator class="my-6 lg:hidden" />

        <div class="flex-1 md:max-w-2xl">
            <section class="max-w-xl space-y-12">
                {@render children?.()}
            </section>
        </div>
    </div>
</div>
