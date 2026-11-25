<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import BookOpen from '@lucide/svelte/icons/book-open';
    import House from '@lucide/svelte/icons/house';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import Sparkles from '@lucide/svelte/icons/sparkles';
    import {
        Sheet,
        SheetContent,
        SheetHeader,
        SheetTitle,
    } from '@/components/ui/sheet';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { t } from '@/lib/i18n';
    import { toUrl } from '@/lib/utils';
    import { dashboard, home } from '@/routes';
    import { index as levelsIndex } from '@/routes/levels';
    import { index as quizIndex } from '@/routes/quiz';

    let { open = $bindable(false) }: { open?: boolean } = $props();

    const auth = $derived(page.props.auth);
    const url = currentUrlState();
    const brandHref = $derived(auth.user ? toUrl(dashboard()) : toUrl(home()));

    function close() {
        open = false;
    }
</script>

<Sheet bind:open>
    <SheetContent side="left" class="w-70 p-6">
        <div data-test="mobile-nav-sheet">
            <SheetTitle class="sr-only">{t('ui.nav.menu_title')}</SheetTitle>
            <SheetHeader class="flex flex-row items-center gap-2 text-left">
                <Link
                    href={brandHref}
                    class="text-foreground flex items-center gap-2 font-bold"
                    onclick={close}
                >
                    <img
                        src="/favicon.svg"
                        alt=""
                        aria-hidden="true"
                        class="size-8 rounded-lg shadow-xs"
                    />
                    <span class="text-lg font-bold tracking-tight"
                        >Vomorize</span
                    >
                </Link>
            </SheetHeader>

            <div class="pt-6">
                <nav class="space-y-1">
                    {#if auth.user}
                        <Link
                            href={toUrl(dashboard())}
                            class={{
                                'hover:bg-accent hover:text-accent-foreground flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors': true,
                                'bg-accent text-foreground font-semibold':
                                    url.isCurrentUrl(
                                        dashboard(),
                                        url.currentUrl,
                                    ),
                                'text-muted-foreground': !url.isCurrentUrl(
                                    dashboard(),
                                    url.currentUrl,
                                ),
                            }}
                            onclick={close}
                        >
                            <LayoutGrid class="size-4.5" />
                            <span>{t('ui.nav.dashboard')}</span>
                        </Link>
                    {:else}
                        <Link
                            href={toUrl(home())}
                            class={{
                                'hover:bg-accent hover:text-accent-foreground flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors': true,
                                'bg-accent text-foreground font-semibold':
                                    url.isCurrentUrl(home(), url.currentUrl),
                                'text-muted-foreground': !url.isCurrentUrl(
                                    home(),
                                    url.currentUrl,
                                ),
                            }}
                            onclick={close}
                        >
                            <House class="size-4.5" />
                            <span>{t('ui.nav.home')}</span>
                        </Link>
                    {/if}

                    <Link
                        href={levelsIndex.url()}
                        class={{
                            'hover:bg-accent hover:text-accent-foreground flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors': true,
                            'bg-accent text-foreground font-semibold':
                                url.isCurrentOrParentUrl(
                                    '/levels',
                                    url.currentUrl,
                                ),
                            'text-muted-foreground': !url.isCurrentOrParentUrl(
                                '/levels',
                                url.currentUrl,
                            ),
                        }}
                        onclick={close}
                    >
                        <BookOpen class="size-4.5" />
                        <span>{t('ui.nav.levels')}</span>
                    </Link>

                    <Link
                        href={quizIndex.url()}
                        class={{
                            'hover:bg-accent hover:text-accent-foreground flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors': true,
                            'bg-accent text-foreground font-semibold':
                                url.isCurrentOrParentUrl(
                                    '/quiz',
                                    url.currentUrl,
                                ),
                            'text-muted-foreground': !url.isCurrentOrParentUrl(
                                '/quiz',
                                url.currentUrl,
                            ),
                        }}
                        onclick={close}
                    >
                        <Sparkles class="size-4.5" />
                        <span>{t('ui.nav.custom_quiz')}</span>
                    </Link>
                </nav>
            </div>
        </div>
    </SheetContent>
</Sheet>
