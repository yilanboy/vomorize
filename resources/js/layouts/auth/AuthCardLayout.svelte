<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import type { Snippet } from 'svelte';
    import SiteLanguageSwitcher from '@/components/SiteLanguageSwitcher.svelte';
    import ThemeToggle from '@/components/ThemeToggle.svelte';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { t } from '@/lib/i18n';
    import { home } from '@/routes';

    let {
        title = '',
        description = '',
        children,
    }: {
        title?: string;
        description?: string;
        children?: Snippet;
    } = $props();
</script>

<div
    class="bg-background text-foreground selection:bg-primary relative flex min-h-svh flex-col justify-between overflow-x-hidden selection:text-white"
>
    <!-- Background Ambient Glow -->
    <div
        aria-hidden="true"
        class="pointer-events-none fixed inset-0 -z-10 overflow-hidden"
    >
        <div
            class="bg-primary/10 dark:bg-primary/5 absolute -top-[25%] left-1/2 h-[550px] w-[900px] -translate-x-1/2 rounded-full blur-[140px]"
        ></div>
        <div
            class="absolute right-[15%] -bottom-[15%] h-[450px] w-[450px] rounded-full bg-emerald-500/10 blur-[130px] dark:bg-emerald-500/5"
        ></div>
    </div>

    <!-- Top Utility Bar -->
    <header
        class="relative z-20 flex w-full items-center justify-between px-5 py-4 sm:px-8 sm:py-5"
    >
        <Link
            href={home()}
            class="group text-muted-foreground hover:text-foreground inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors hover:bg-zinc-200/50 dark:hover:bg-zinc-800"
        >
            <ArrowLeft
                class="size-4 transition-transform duration-150 group-hover:-translate-x-0.5"
            />
            <span>{t('ui.auth.back_to_home')}</span>
        </Link>
        <div class="flex items-center gap-2">
            <SiteLanguageSwitcher />
            <ThemeToggle />
        </div>
    </header>

    <!-- Center Floating Card -->
    <main
        class="relative z-10 flex flex-1 items-center justify-center px-4 py-8"
    >
        <div class="w-full max-w-[460px] transition-all">
            <Card
                class="border-border/80 bg-card/95 shadow-surface-dim/40 dark:border-border/70 dark:bg-card/90 rounded-2xl p-7 shadow-xl backdrop-blur-xs sm:p-9 dark:shadow-none"
            >
                {#if title || description}
                    <CardHeader class="space-y-2 p-0 pb-7 text-center">
                        {#if title}
                            <CardTitle
                                class="text-foreground text-2xl font-bold tracking-tight sm:text-3xl"
                            >
                                {title}
                            </CardTitle>
                        {/if}
                        {#if description}
                            <CardDescription
                                class="text-muted-foreground text-sm leading-relaxed"
                            >
                                {description}
                            </CardDescription>
                        {/if}
                    </CardHeader>
                {/if}
                <CardContent class="p-0">
                    {@render children?.()}
                </CardContent>
            </Card>
        </div>
    </main>

    <!-- Page Footer -->
    <footer
        class="text-muted-foreground/80 relative z-10 w-full py-6 text-center text-sm"
    >
        <span
            >{t('ui.footer.copyright', {
                year: new Date().getFullYear(),
            })}</span
        >
        <span class="mx-2">·</span>
        <span>{t('ui.auth.tagline')}</span>
    </footer>
</div>
