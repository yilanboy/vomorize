<script lang="ts">
    import { page, Link } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import LanguageSwitcher from '@/components/LanguageSwitcher.svelte';
    import ThemeToggle from '@/components/ThemeToggle.svelte';
    import UserMenuContent from '@/components/UserMenuContent.svelte';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { getFirstCharacter } from '@/lib/initials';
    import { currentLocale, translations } from '@/lib/locale.svelte';
    import { getGuestProgressMap, clearGuestProgress } from '@/lib/progress';

    let migrationMessage = $state(false);

    onMount(async () => {
        const user = page.props.auth?.user;

        if (!user) {
            return;
        }

        const map = getGuestProgressMap();
        const list = Object.values(map);

        if (list.length === 0) {
            return;
        }

        try {
            const res = await fetch('/progress/migrate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
                            ?.content || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ guest_progress: list }),
            });

            if (res.ok) {
                clearGuestProgress();
                migrationMessage = true;
                setTimeout(() => {
                    migrationMessage = false;
                }, 5000);
            }
        } catch {
            // Ignore migration failure
        }
    });

    let availableLocales = $derived(
        (page.props.available_locales as Record<string, string>) || { zh_TW: '繁體中文' },
    );
    let t = $derived(translations());
</script>

<header class="sticky top-0 z-40 border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3 sm:px-6">
        <div class="flex items-center space-x-6">
            <Link
                href="/"
                class="flex items-center space-x-2 text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50"
            >
                <svg
                    class="h-7 w-7"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                    />
                </svg>
                <span>Vomorize</span>
            </Link>

            <nav class="hidden space-x-4 sm:flex">
                <Link
                    href="/"
                    class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
                >
                    {t['home']}
                </Link>
                <Link
                    href="/quiz/custom"
                    class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
                >
                    {t['custom_quiz']}
                </Link>
            </nav>
        </div>

        <div class="flex items-center space-x-3">
            <LanguageSwitcher currentLocale={currentLocale()} {availableLocales} />
            <ThemeToggle />

            {#if page.props.auth?.user}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        {#snippet children(props)}
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-9 rounded-full focus-visible:ring-2 focus-visible:ring-zinc-400 dark:focus-visible:ring-zinc-600"
                                onclick={props.onclick}
                                aria-expanded={props['aria-expanded']}
                                data-state={props['data-state']}
                            >
                                <Avatar class="size-9 rounded-full">
                                    <AvatarFallback
                                        class="rounded-full bg-zinc-200 font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50"
                                    >
                                        {getFirstCharacter(page.props.auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        {/snippet}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent user={page.props.auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            {:else}
                <div class="flex items-center space-x-2">
                    <Link
                        href="/login"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
                    >
                        {t['login']}
                    </Link>
                    <Link
                        href="/register"
                        class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white shadow-xs hover:bg-zinc-800 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-200"
                    >
                        {t['register']}
                    </Link>
                </div>
            {/if}
        </div>
    </div>

    {#if migrationMessage}
        <div
            class="border-t border-zinc-200 bg-zinc-100 px-4 py-2 text-center text-sm font-medium text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400"
        >
            {t['guest_progress_migrated']}
        </div>
    {/if}
</header>
