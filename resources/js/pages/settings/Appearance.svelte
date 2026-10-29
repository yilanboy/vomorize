<script lang="ts">
    import Palette from '@lucide/svelte/icons/palette';
    import AppearanceTabs from '@/components/AppearanceTabs.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { t } from '@/lib/i18n';
</script>

<AppHead title={t('ui.settings.appearance.title')} />

<h1 class="sr-only">{t('ui.settings.appearance.title')}</h1>

<div class="border-border/80 bg-card rounded-2xl border p-6 shadow-xs sm:p-7">
    <header class="mb-6 flex items-start gap-3">
        <div
            class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl"
        >
            <Palette class="size-5" />
        </div>
        <div>
            <h2 class="text-foreground text-lg font-semibold tracking-tight">
                {t('ui.settings.appearance.title')}
            </h2>
            <p class="text-muted-foreground mt-0.5 text-sm">
                {t('ui.settings.appearance.subtitle')}
            </p>
        </div>
    </header>

    <AppearanceTabs />
</div>
