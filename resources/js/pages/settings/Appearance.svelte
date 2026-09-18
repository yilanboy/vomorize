<script lang="ts">
    import AppearanceTabs from '@/components/AppearanceTabs.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { t } from '@/lib/i18n';
</script>

<AppHead title={t('ui.settings.appearance.title')} />

<h1 class="sr-only">{t('ui.settings.appearance.title')}</h1>

<div class="space-y-6">
    <Heading
        variant="small"
        title={t('ui.settings.appearance.title')}
        description={t('ui.settings.appearance.subtitle')}
    />
    <AppearanceTabs />
</div>
