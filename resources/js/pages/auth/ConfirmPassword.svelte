<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
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

<div class="space-y-6">
    <!-- Passkey Quick Confirmation (placed at top when supported) -->
    <PasskeyVerify
        routes={{
            options: confirmOptions(),
            submit: confirmStore(),
        }}
        label={t('ui.auth.confirm_password.passkey_button')}
        loadingLabel={t('ui.auth.confirm_password.passkey_loading')}
        separator={t('ui.auth.confirm_password.or_password')}
        showSeparator={true}
        separatorPosition="after"
        class="border-border/80 hover:bg-accent/60 h-12 w-full rounded-xl text-sm font-medium transition-all active:scale-[0.99]"
    />

    <!-- Password Confirmation Form -->
    <Form {...store.form()} resetOnSuccess class="flex flex-col gap-4">
        {#snippet children({ errors, processing })}
            <div class="grid gap-1.5">
                <Label
                    for="password"
                    class="text-foreground text-sm font-semibold"
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
                    class="h-12 rounded-xl text-sm"
                />
                <InputError message={errors.password} />
            </div>

            <Button
                type="submit"
                class="bg-primary text-primary-foreground hover:bg-primary/90 mt-1 flex h-12 w-full items-center justify-center gap-2 rounded-xl text-sm font-semibold shadow-xs transition-all active:scale-[0.99]"
                disabled={processing}
                data-test="confirm-password-button"
            >
                {#if processing}
                    <Spinner class="size-4" />
                {/if}
                <span>{t('ui.auth.confirm_password.submit')}</span>
                <ArrowRight class="size-4" />
            </Button>
        {/snippet}
    </Form>
</div>
