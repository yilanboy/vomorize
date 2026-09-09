<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import BookOpen from '@lucide/svelte/icons/book-open';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import Sparkles from '@lucide/svelte/icons/sparkles';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import SiteLanguageSwitcher from '@/components/SiteLanguageSwitcher.svelte';
    import {
        Sheet,
        SheetContent,
        SheetHeader,
        SheetTitle,
    } from '@/components/ui/sheet';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { t } from '@/lib/i18n';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';

    let { open = $bindable(false) }: { open?: boolean } = $props();

    const auth = $derived(page.props.auth);
    const url = currentUrlState();

    function close() {
        open = false;
    }
</script>

<Sheet bind:open>
    <SheetContent
        side="left"
        class="flex w-[280px] flex-col justify-between p-6"
    >
        <div>
            <SheetTitle class="sr-only">{t('ui.nav.menu_title')}</SheetTitle>
            <SheetHeader class="flex flex-row items-center gap-2 text-left">
                <Link
                    href="/levels"
                    class="text-foreground flex items-center gap-2 font-bold"
                    onclick={close}
                >
                    <div
                        class="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-lg shadow-xs"
                    >
                        <AppLogoIcon class="size-5 fill-current" />
                    </div>
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
                    {/if}

                    <Link
                        href="/levels"
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
                        href="/quiz"
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
                        <span>{t('ui.nav.quiz')}</span>
                    </Link>
                </nav>
            </div>
        </div>

        <SiteLanguageSwitcher variant="mobile" />
    </SheetContent>
</Sheet>
