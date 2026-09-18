<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
    import { login } from '@/routes';
    import { email } from '@/routes/password';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();

    setLayoutProps({
        title: t('ui.auth.forgot_password.title'),
        description: t('ui.auth.forgot_password.subtitle'),
    });
</script>

<AppHead title={t('ui.auth.forgot_password.title')} />

<div class="space-y-5">
    {#if status}
        <div
            class="rounded-xl border border-emerald-500/20 bg-emerald-50/50 p-3 text-center text-sm font-medium text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400"
        >
            {status}
        </div>
    {/if}

    <Form {...email.form()} class="flex flex-col gap-5">
        {#snippet children({ errors, processing })}
            <div class="grid gap-4">
                <div class="grid gap-1.5">
                    <Label
                        for="email"
                        class="text-foreground text-xs font-semibold"
                    >
                        {t('ui.auth.forgot_password.email')}
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        placeholder={t(
                            'ui.auth.forgot_password.email_placeholder',
                        )}
                        class="h-11 rounded-xl"
                    />
                    <InputError message={errors.email} />
                </div>

                <Button
                    type="submit"
                    class="mt-1 h-11 w-full rounded-xl font-semibold shadow-xs"
                    disabled={processing}
                    data-test="email-password-reset-link-button"
                >
                    {#if processing}<Spinner class="mr-2" />{/if}
                    {t('ui.auth.forgot_password.submit')}
                </Button>
            </div>

            <div class="text-muted-foreground pt-3 text-center text-sm">
                <span>{t('ui.auth.forgot_password.or_return_to')}</span>
                <TextLink
                    href={login()}
                    class="text-primary ml-1 font-semibold hover:underline"
                >
                    {t('ui.auth.forgot_password.back_to_login')}
                </TextLink>
            </div>
        {/snippet}
    </Form>
</div>
