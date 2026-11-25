<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Menu from '@lucide/svelte/icons/menu';
    import { onMount } from 'svelte';
    import { on } from 'svelte/events';
    import MobileNavSheet from '@/components/MobileNavSheet.svelte';
    import SiteLanguageSwitcher from '@/components/SiteLanguageSwitcher.svelte';
    import ThemeToggle from '@/components/ThemeToggle.svelte';
    import {
        Avatar,
        AvatarFallback,
        AvatarImage,
    } from '@/components/ui/avatar';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import UserMenuContent from '@/components/UserMenuContent.svelte';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { t } from '@/lib/i18n';
    import { getInitials } from '@/lib/initials';
    import { toUrl } from '@/lib/utils';
    import { dashboard, home, login } from '@/routes';
    import { index as levelsIndex } from '@/routes/levels';
    import { index as quizIndex } from '@/routes/quiz';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        class: className = '',
    }: {
        breadcrumbs?: BreadcrumbItem[];
        class?: string;
    } = $props();

    const auth = $derived(page.props.auth);
    const url = currentUrlState();
    const brandHref = $derived(auth.user ? toUrl(dashboard()) : toUrl(home()));

    let mobileNavOpen = $state(false);
    let scrolled = $state(false);

    const navItems = $derived.by(() => {
        const items = auth.user
            ? [
                  {
                      href: toUrl(dashboard()),
                      label: t('ui.nav.dashboard'),
                      active: url.isCurrentUrl(dashboard(), url.currentUrl),
                  },
              ]
            : [
                  {
                      href: toUrl(home()),
                      label: t('ui.nav.home'),
                      active: url.isCurrentUrl(home(), url.currentUrl),
                  },
              ];

        items.push(
            {
                href: levelsIndex.url(),
                label: t('ui.nav.levels'),
                active: url.isCurrentOrParentUrl('/levels', url.currentUrl),
            },
            {
                href: quizIndex.url(),
                label: t('ui.nav.custom_quiz'),
                active: url.isCurrentOrParentUrl('/quiz', url.currentUrl),
            },
        );

        return items;
    });

    onMount(() => {
        const updateScrolled = () => {
            scrolled = window.scrollY > 12;
        };

        updateScrolled();

        return on(window, 'scroll', updateScrolled, { passive: true });
    });
</script>

<header
    data-test="app-header"
    class={{
        'sticky top-0 z-40 h-16 w-full border-b transition-[background-color,border-color,backdrop-filter] duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none': true,
        'border-transparent bg-transparent': !scrolled,
        'border-border/80 bg-background/88 backdrop-blur-md': scrolled,
        [className]: Boolean(className),
    }}
>
    <div
        class="mx-auto flex h-full max-w-7xl items-center justify-between gap-2 px-4 sm:gap-4 sm:px-6 lg:px-8"
    >
        <!-- Left: Mobile Menu Toggle & Brand Logo -->
        <div class="flex items-center gap-2 lg:gap-6">
            <button
                type="button"
                class="text-foreground/75 inline-flex size-9 cursor-pointer items-center justify-center rounded-full transition-[background-color,color] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] hover:bg-blue-600/8 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none lg:hidden dark:hover:bg-blue-400/12 dark:hover:text-blue-400"
                onclick={() => (mobileNavOpen = true)}
                aria-label={t('ui.nav.open_menu')}
                data-test="mobile-menu-button"
            >
                <Menu class="size-5" />
            </button>

            <Link
                href={brandHref}
                class="text-foreground flex items-center gap-2.5 font-bold transition-opacity duration-150 hover:opacity-90 motion-reduce:transition-none"
            >
                <img
                    src="/favicon.svg"
                    alt=""
                    aria-hidden="true"
                    class="size-7 rounded-lg shadow-xs sm:size-8"
                />
                <span class="text-lg font-extrabold tracking-tight sm:text-xl"
                    >Vomorize</span
                >
            </Link>
        </div>

        <!-- Center: Desktop Navigation Links (lg:) -->
        <nav class="hidden items-center gap-7 lg:flex">
            {#each navItems as item (item.href)}
                <Link
                    href={item.href}
                    class={{
                        'relative rounded-xs py-1 text-sm transition-colors duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] after:absolute after:inset-x-0 after:bottom-0 after:h-[1.5px] after:origin-left after:bg-blue-600 after:transition-transform after:duration-200 after:ease-[cubic-bezier(0.22,1,0.36,1)] focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none motion-reduce:transition-none motion-reduce:after:transition-none dark:after:bg-blue-400': true,
                        'text-foreground font-semibold after:scale-x-100':
                            item.active,
                        'text-foreground/75 font-medium after:scale-x-0 hover:text-blue-600 hover:after:scale-x-100 dark:hover:text-blue-400':
                            !item.active,
                    }}
                >
                    {item.label}
                </Link>
            {/each}
        </nav>

        <!-- Right: SiteLanguageSwitcher + ThemeToggle + Member Avatar / Login -->
        <div class="flex items-center gap-1.5 sm:gap-2">
            <SiteLanguageSwitcher />

            <ThemeToggle />

            <span
                aria-hidden="true"
                class="bg-border/80 hidden h-4 w-px sm:block"
            ></span>

            {#if auth.user}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        {#snippet children(props)}
                            <button
                                type="button"
                                class="ring-offset-background focus-visible:ring-ring relative inline-flex size-9 cursor-pointer items-center justify-center rounded-full p-0 transition-shadow duration-150 hover:ring-2 hover:ring-blue-500/50 focus-visible:ring-2 focus-visible:outline-none motion-reduce:transition-none"
                                onclick={props.onclick}
                                aria-expanded={props['aria-expanded']}
                                data-state={props['data-state']}
                            >
                                <Avatar class="size-8">
                                    {#if auth.user?.avatar}
                                        <AvatarImage
                                            src={auth.user.avatar}
                                            alt={auth.user?.name}
                                        />
                                    {/if}
                                    <AvatarFallback
                                        class="bg-muted text-foreground font-semibold"
                                    >
                                        {getInitials(auth.user?.name ?? '')}
                                    </AvatarFallback>
                                </Avatar>
                            </button>
                        {/snippet}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            {:else}
                <Link
                    href={toUrl(login())}
                    class="border-border/90 bg-background/70 text-foreground dark:bg-background/50 inline-flex h-9 items-center justify-center rounded-lg border px-4 text-sm font-medium whitespace-nowrap backdrop-blur-xs transition-[background-color,border-color,color,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)] hover:border-blue-500/60 hover:bg-blue-600/8 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none active:scale-[0.98] motion-reduce:transition-none dark:hover:border-blue-400/60 dark:hover:bg-blue-400/12 dark:hover:text-blue-400"
                >
                    {t('ui.nav.login')}
                </Link>
            {/if}
        </div>
    </div>
</header>

<MobileNavSheet bind:open={mobileNavOpen} />
