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
            class="bg-primary/10 dark:bg-primary/5 absolute -top-[20%] left-[20%] h-[520px] w-[520px] rounded-full blur-[130px]"
        ></div>
        <div
            class="absolute right-[15%] -bottom-[20%] h-[520px] w-[520px] rounded-full bg-emerald-500/10 blur-[140px] dark:bg-emerald-500/5"
        ></div>
    </div>

    <!-- Top Utility Bar -->
    <header
        class="relative z-20 flex w-full items-center justify-between px-5 py-4 sm:px-8 sm:py-5"
    >
        <Link
            href={home()}
            class="text-muted-foreground hover:text-foreground hover:bg-accent/70 inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium transition-colors"
        >
            <ArrowLeft class="size-4" />
            <span>{t('ui.auth.back_to_home')}</span>
        </Link>
        <div class="flex items-center gap-2">
            <SiteLanguageSwitcher variant="desktop" />
            <ThemeToggle />
        </div>
    </header>

    <!-- Center Floating Card -->
    <main class="flex flex-1 items-center justify-center px-4 py-8">
        <div class="w-full max-w-[440px] transition-all">
            <Card
                class="border-border/70 bg-card/95 shadow-surface-dim/40 rounded-2xl p-7 shadow-xl backdrop-blur-xs sm:p-9 dark:shadow-none"
            >
                {#if title || description}
                    <CardHeader class="space-y-1.5 p-0 pb-6 text-center">
                        {#if title}
                            <CardTitle
                                class="text-foreground text-2xl font-bold tracking-tight"
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
    <footer class="text-muted-foreground w-full py-5 text-center text-xs">
        <span
            >{t('ui.footer.copyright', {
                year: new Date().getFullYear(),
            })}</span
        >
        <span class="mx-1.5">·</span>
        <span>{t('ui.auth.tagline')}</span>
    </footer>
</div>
