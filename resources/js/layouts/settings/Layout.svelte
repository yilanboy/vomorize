<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Palette from '@lucide/svelte/icons/palette';
    import Shield from '@lucide/svelte/icons/shield';
    import User from '@lucide/svelte/icons/user';
    import type { Component, Snippet } from 'svelte';
    import { Separator } from '@/components/ui/separator';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { t } from '@/lib/i18n';
    import { toUrl } from '@/lib/utils';
    import { edit as editAppearance } from '@/routes/appearance';
    import { edit as editProfile } from '@/routes/profile';
    import { edit as editSecurity } from '@/routes/security';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    interface SettingsNavItem {
        title: string;
        href: string;
        icon: Component<{ class?: string }>;
    }

    const sidebarNavItems: SettingsNavItem[] = $derived([
        {
            title: t('ui.settings.layout.nav.profile'),
            href: toUrl(editProfile()),
            icon: User,
        },
        {
            title: t('ui.settings.layout.nav.security'),
            href: toUrl(editSecurity()),
            icon: Shield,
        },
        {
            title: t('ui.settings.layout.nav.appearance'),
            href: toUrl(editAppearance()),
            icon: Palette,
        },
    ]);

    const url = currentUrlState();
</script>

<div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <header class="mb-8 space-y-1.5">
        <h1
            class="text-foreground text-2xl font-bold tracking-tight sm:text-3xl"
        >
            {t('ui.settings.layout.title')}
        </h1>
        <p class="text-muted-foreground text-sm leading-relaxed">
            {t('ui.settings.layout.subtitle')}
        </p>
    </header>

    <div class="flex flex-col lg:flex-row lg:gap-10">
        <!-- Navigation Sidebar / Mobile Pill Strip -->
        <aside class="w-full shrink-0 lg:w-56">
            <nav
                class="flex gap-1.5 overflow-x-auto pb-4 lg:flex-col lg:overflow-x-visible lg:pb-0"
                aria-label={t('ui.settings.layout.title')}
            >
                {#each sidebarNavItems as item (item.href)}
                    {@const isActive = url.isCurrentUrl(
                        item.href,
                        url.currentUrl,
                    )}
                    <Link
                        href={item.href}
                        class={{
                            'flex h-11 shrink-0 items-center gap-3 rounded-xl px-3.5 text-sm font-medium whitespace-nowrap transition-all select-none lg:w-full': true,
                            'bg-primary/10 text-primary dark:bg-primary/15 font-semibold shadow-xs':
                                isActive,
                            'text-muted-foreground hover:bg-accent/60 hover:text-foreground':
                                !isActive,
                        }}
                    >
                        <item.icon class="size-4 shrink-0" />
                        <span>{item.title}</span>
                    </Link>
                {/each}
            </nav>
        </aside>

        <Separator class="my-6 lg:hidden" />

        <!-- Main Content Surface -->
        <main class="w-full max-w-2xl flex-1">
            <div class="space-y-8">
                {@render children?.()}
            </div>
        </main>
    </div>
</div>
