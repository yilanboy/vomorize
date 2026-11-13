<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
    import { logout } from '@/routes';
    import { send } from '@/routes/verification';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();

    setLayoutProps({
        title: t('ui.auth.verify_email.title'),
        description: t('ui.auth.verify_email.subtitle'),
    });
</script>

<AppHead title={t('ui.auth.verify_email.title')} />

{#if status === 'verification-link-sent'}
    <div
        class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-50/50 p-3 text-center text-sm font-medium text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400"
    >
        {t('ui.auth.verify_email.sent_notice')}
    </div>
{/if}

<Form {...send.form()} class="space-y-6 text-center">
    {#snippet children({ processing })}
        <Button
            type="submit"
            disabled={processing}
            variant="secondary"
            class="h-11 w-full rounded-xl font-semibold shadow-xs"
        >
            {#if processing}<Spinner class="mr-2" />{/if}
            {t('ui.auth.verify_email.resend_button')}
        </Button>

        <TextLink
            href={logout()}
            as="button"
            class="text-muted-foreground hover:text-foreground mx-auto block text-sm transition-colors"
        >
            {t('ui.auth.verify_email.logout')}
        </TextLink>
    {/snippet}
</Form>
