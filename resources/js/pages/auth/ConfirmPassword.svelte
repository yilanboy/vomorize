<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import {
        index as confirmOptions,
        store as confirmStore,
    } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasskeyVerify from '@/components/PasskeyVerify.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
    import { store } from '@/routes/password/confirm';

    setLayoutProps({
        title: t('ui.auth.confirm_password.title'),
        description: t('ui.auth.confirm_password.subtitle'),
    });
</script>

<AppHead title={t('ui.auth.confirm_password.title')} />

<div class="space-y-5">
    <Form {...store.form()} resetOnSuccess class="flex flex-col gap-5">
        {#snippet children({ errors, processing })}
            <div class="grid gap-4">
                <div class="grid gap-1.5">
                    <Label
                        for="password"
                        class="text-foreground text-xs font-semibold"
                    >
                        {t('ui.auth.confirm_password.password')}
                    </Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder={t(
                            'ui.auth.confirm_password.password_placeholder',
                        )}
                        class="h-11 rounded-xl"
                    />
                    <InputError message={errors.password} />
                </div>

                <Button
                    type="submit"
                    class="mt-1 h-11 w-full rounded-xl font-semibold shadow-xs"
                    disabled={processing}
                    data-test="confirm-password-button"
                >
                    {#if processing}<Spinner class="mr-2" />{/if}
                    {t('ui.auth.confirm_password.submit')}
                </Button>
            </div>
        {/snippet}
    </Form>

    <PasskeyVerify
        routes={{
            options: confirmOptions(),
            submit: confirmStore(),
        }}
        label={t('ui.auth.confirm_password.passkey_button')}
        loadingLabel={t('ui.auth.confirm_password.passkey_loading')}
        separator={t('ui.auth.confirm_password.or_passkey')}
        separatorPosition="before"
        class="border-border/70 hover:bg-accent/60 h-11 rounded-xl font-medium transition-colors"
    />
</div>
