<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Menu from '@lucide/svelte/icons/menu';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import MobileNavSheet from '@/components/MobileNavSheet.svelte';
    import SiteLanguageSwitcher from '@/components/SiteLanguageSwitcher.svelte';
    import ThemeToggle from '@/components/ThemeToggle.svelte';
    import {
        Avatar,
        AvatarFallback,
        AvatarImage,
    } from '@/components/ui/avatar';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import UserMenuContent from '@/components/UserMenuContent.svelte';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { t } from '@/lib/i18n';
    import { getInitials } from '@/lib/initials';
    import { cn, toUrl } from '@/lib/utils';
    import { dashboard, login } from '@/routes';
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

    let mobileNavOpen = $state(false);
</script>

<header
    class={cn(
        'border-border bg-background sticky top-0 z-40 h-16 w-full border-b transition-colors duration-300',
        className,
    )}
>
    <div
        class="mx-auto flex h-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
    >
        <!-- Left: Mobile Menu Toggle & Brand Logo -->
        <div class="flex items-center gap-2 lg:gap-6">
            <!-- Mobile Menu Button (< lg) -->
            <button
                type="button"
                class="inline-flex size-9 cursor-pointer items-center justify-center rounded-lg text-zinc-500 transition-colors duration-200 hover:bg-zinc-200/50 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-200 lg:hidden dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 dark:focus-visible:ring-zinc-700"
                onclick={() => (mobileNavOpen = true)}
                aria-label={t('ui.nav.open_menu')}
            >
                <Menu class="size-5" />
            </button>

            <!-- Brand Logo -->
            <Link
                href="/levels"
                class="text-foreground flex items-center gap-2.5 font-bold transition-opacity hover:opacity-90"
            >
                <div
                    class="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-lg shadow-xs"
                >
                    <AppLogoIcon class="size-5 fill-current" />
                </div>
                <span class="text-xl font-bold tracking-tight">Vomorize</span>
            </Link>
        </div>

        <!-- Center: Desktop Navigation Links (lg:) -->
        <nav class="hidden items-center gap-1.5 lg:flex">
            {#if auth.user}
                <Link
                    href={toUrl(dashboard())}
                    class={{
                        'inline-flex items-center rounded-lg px-3.5 py-2 text-sm font-medium transition-colors': true,
                        'bg-accent text-foreground font-semibold':
                            url.isCurrentUrl(dashboard(), url.currentUrl),
                        'text-muted-foreground hover:bg-accent/60 hover:text-foreground':
                            !url.isCurrentUrl(dashboard(), url.currentUrl),
                    }}
                >
                    {t('ui.nav.dashboard')}
                </Link>
            {/if}

            <Link
                href="/levels"
                class={{
                    'inline-flex items-center rounded-lg px-3.5 py-2 text-sm font-medium transition-colors': true,
                    'bg-accent text-foreground font-semibold':
                        url.isCurrentOrParentUrl('/levels', url.currentUrl),
                    'text-muted-foreground hover:bg-accent/60 hover:text-foreground':
                        !url.isCurrentOrParentUrl('/levels', url.currentUrl),
                }}
            >
                {t('ui.nav.levels')}
            </Link>

            <Link
                href="/quiz"
                class={{
                    'inline-flex items-center rounded-lg px-3.5 py-2 text-sm font-medium transition-colors': true,
                    'bg-accent text-foreground font-semibold':
                        url.isCurrentOrParentUrl('/quiz', url.currentUrl),
                    'text-muted-foreground hover:bg-accent/60 hover:text-foreground':
                        !url.isCurrentOrParentUrl('/quiz', url.currentUrl),
                }}
            >
                {t('ui.nav.quiz')}
            </Link>
        </nav>

        <!-- Right: SiteLanguageSwitcher + ThemeToggle + Member Avatar / Login -->
        <div class="flex items-center gap-1.5 sm:gap-2">
            <!-- Site Language Switcher (Desktop only; mobile uses drawer) -->
            <SiteLanguageSwitcher class="hidden lg:inline-flex" />

            <!-- Theme Toggle Dropdown -->
            <ThemeToggle />

            <!-- Member Avatar or Guest Login -->
            {#if auth.user}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        {#snippet children(props)}
                            <Button
                                variant="ghost"
                                size="icon"
                                class="ring-offset-background hover:ring-primary/40 focus-visible:ring-ring relative size-9 cursor-pointer rounded-full p-0 transition-all hover:ring-2 focus-visible:ring-2"
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
                            </Button>
                        {/snippet}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            {:else}
                <Button asChild size="sm">
                    {#snippet children(props)}
                        <Link href={toUrl(login())} class={props.class}>
                            {t('ui.nav.login')}
                        </Link>
                    {/snippet}
                </Button>
            {/if}
        </div>
    </div>
</header>

<!-- Mobile Navigation Drawer -->
<MobileNavSheet bind:open={mobileNavOpen} />
