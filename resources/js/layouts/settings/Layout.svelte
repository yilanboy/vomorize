<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Shield from '@lucide/svelte/icons/shield';
    import User from '@lucide/svelte/icons/user';
    import type { Snippet } from 'svelte';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { translations } from '@/lib/locale.svelte';
    import { toUrl } from '@/lib/utils';
    import { edit as editProfile } from '@/routes/profile';
    import { edit as editSecurity } from '@/routes/security';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    let t = $derived(translations());

    const sidebarNavItems = $derived([
        {
            title: t['profile_settings'],
            href: editProfile(),
            icon: User,
        },
        {
            title: t['security_settings'],
            href: editSecurity(),
            icon: Shield,
        },
    ]);

    const url = currentUrlState();
</script>

<main class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-50">
            {t['settings']}
        </h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {t['settings_subtitle']}
        </p>
    </div>

    <div class="flex flex-col gap-6 md:flex-row md:gap-8">
        <aside class="w-full shrink-0 md:w-52">
            <nav
                class="flex flex-row gap-1.5 overflow-x-auto pb-2 md:flex-col md:overflow-visible md:pb-0"
                aria-label="Settings navigation"
            >
                {#each sidebarNavItems as item (toUrl(item.href))}
                    {@const isActive = url.isCurrentUrl(item.href, url.currentUrl)}
                    <Link
                        href={toUrl(item.href)}
                        class="flex shrink-0 items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all {isActive
                            ? 'border border-zinc-200 bg-zinc-200 font-semibold text-zinc-900 shadow-2xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-50'
                            : 'text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50'}"
                    >
                        <item.icon
                            class="h-4 w-4 shrink-0 {isActive
                                ? 'text-zinc-900 dark:text-zinc-50'
                                : 'text-zinc-500 dark:text-zinc-400'}"
                        />
                        <span>{item.title}</span>
                    </Link>
                {/each}
            </nav>
        </aside>

        <div class="min-w-0 flex-1">
            {@render children?.()}
        </div>
    </div>
</main>
