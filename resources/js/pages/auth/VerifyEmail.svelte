<script module lang="ts">
    export const layout = {
        title: 'verify_email_title',
        description: 'verify_email_subtitle',
    };
</script>

<script lang="ts">
    import { page, Form, Link } from '@inertiajs/svelte';
    import Mail from '@lucide/svelte/icons/mail';
    import LogOut from '@lucide/svelte/icons/log-out';
    import AppHead from '@/components/AppHead.svelte';
    import LanguageSwitcher from '@/components/LanguageSwitcher.svelte';
    import ThemeToggle from '@/components/ThemeToggle.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { translations } from '@/lib/locale.svelte';
    import { logout } from '@/routes';
    import { send } from '@/routes/verification';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();

    let user = $derived(page.props.auth?.user);
    let t = $derived(translations());
</script>

<AppHead title={t['verify_email_title']} />

<div
    class="flex min-h-screen flex-col justify-between bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-50"
>
    <header
        class="border-b border-zinc-200/60 bg-zinc-100/80 px-4 py-3 backdrop-blur-md sm:px-6 dark:border-zinc-800/60 dark:bg-zinc-950/80"
    >
        <div class="mx-auto flex max-w-4xl items-center justify-between">
            <div
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
            </div>

            <div class="flex items-center space-x-3">
                <ThemeToggle />
                <LanguageSwitcher />

                {#if user}
                    <Link
                        href={logout()}
                        method="post"
                        as="button"
                        class="flex items-center space-x-1.5 rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
                    >
                        <LogOut class="h-3.5 w-3.5" />
                        <span>{t['logout']}</span>
                    </Link>
                {/if}
            </div>
        </div>
    </header>

    <main class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6">
        <div class="w-full max-w-md space-y-6">
            <div
                class="rounded-2xl border border-zinc-200 bg-zinc-50 p-8 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 text-zinc-900 ring-8 ring-zinc-100/50 dark:bg-zinc-800 dark:text-zinc-50 dark:ring-zinc-800/50"
                >
                    <Mail class="h-8 w-8" />
                </div>

                <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">
                    {t['verify_email_title']}
                </h1>
                <p class="mt-2 text-base text-zinc-500 dark:text-zinc-400">
                    {t['verify_email_subtitle']}
                </p>

                {#if user?.email}
                    <div
                        class="mt-4 rounded-lg bg-zinc-100 px-3.5 py-2 text-sm font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
                    >
                        <span class="text-zinc-500 dark:text-zinc-400">{t['sent_to_email']}</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-50"
                            >{user.email}</span
                        >
                    </div>
                {/if}

                {#if status === 'verification-link-sent'}
                    <div
                        class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm font-medium text-emerald-700 dark:text-emerald-300"
                    >
                        {t['verification_link_sent']}
                    </div>
                {/if}

                <div class="mt-6">
                    <Form {...send.form()} class="w-full">
                        {#snippet children({ processing })}
                            <Button
                                type="submit"
                                disabled={processing}
                                class="w-full bg-zinc-900 py-2.5 font-medium text-white hover:bg-zinc-800 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-200"
                            >
                                {#if processing}<Spinner />{/if}
                                {t['resend_verification_email']}
                            </Button>
                        {/snippet}
                    </Form>
                </div>
            </div>
        </div>
    </main>

    <footer class="py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
        &copy; {new Date().getFullYear()} Vomorize. All rights reserved.
    </footer>
</div>
