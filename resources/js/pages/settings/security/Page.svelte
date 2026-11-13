<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import KeyRound from '@lucide/svelte/icons/key-round';
    import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import ManagePasskeys from '@/components/ManagePasskeys.svelte';
    import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.svelte';
    import ManageTwoFactor from '@/components/ManageTwoFactor.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';

    const canManageTwoFactor = $derived(Boolean(page.props.canManageTwoFactor));
    const requiresConfirmation = $derived(
        Boolean(page.props.requiresConfirmation),
    );
    const twoFactorEnabled = $derived(Boolean(page.props.twoFactorEnabled));
    const canManagePasskeys = $derived(Boolean(page.props.canManagePasskeys));
    const passkeys = $derived(
        (Array.isArray(page.props.passkeys)
            ? page.props.passkeys
            : []) as ManagePasskeysProps['passkeys'],
    );

    let { passwordRules }: { passwordRules: string } = $props();
</script>

<AppHead title={t('ui.settings.security.breadcrumbs')} />

<h1 class="sr-only">{t('ui.settings.security.breadcrumbs')}</h1>

<div class="space-y-8">
    <!-- Password Update Card -->
    <div
        class="border-border/80 bg-card rounded-2xl border p-6 shadow-xs sm:p-7"
    >
        <header class="mb-6 flex items-start gap-3">
            <div
                class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl"
            >
                <KeyRound class="size-5" />
            </div>
            <div>
                <h2
                    class="text-foreground text-lg font-semibold tracking-tight"
                >
                    {t('ui.settings.security.password.title')}
                </h2>
                <p class="text-muted-foreground mt-0.5 text-sm">
                    {t('ui.settings.security.password.subtitle')}
                </p>
            </div>
        </header>

        <Form
            {...SecurityController.update.form()}
            class="space-y-5"
            options={{ preserveScroll: true }}
            resetOnSuccess
            resetOnError={[
                'password',
                'password_confirmation',
                'current_password',
            ]}
        >
            {#snippet children({ errors, processing })}
                <div class="grid gap-1.5">
                    <Label
                        for="current_password"
                        class="text-foreground text-sm font-semibold"
                    >
                        {t('ui.settings.security.password.current_password')}
                    </Label>
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        class="h-12 w-full rounded-xl text-sm"
                        autocomplete="current-password"
                        placeholder={t(
                            'ui.settings.security.password.current_password_placeholder',
                        )}
                    />
                    <InputError message={errors.current_password} />
                </div>

                <div class="grid gap-1.5">
                    <Label
                        for="password"
                        class="text-foreground text-sm font-semibold"
                    >
                        {t('ui.settings.security.password.new_password')}
                    </Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        class="h-12 w-full rounded-xl text-sm"
                        autocomplete="new-password"
                        placeholder={t(
                            'ui.settings.security.password.new_password_placeholder',
                        )}
                        passwordrules={passwordRules}
                    />
                    <InputError message={errors.password} />
                </div>

                <div class="grid gap-1.5">
                    <Label
                        for="password_confirmation"
                        class="text-foreground text-sm font-semibold"
                    >
                        {t('ui.settings.security.password.confirm_password')}
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        class="h-12 w-full rounded-xl text-sm"
                        autocomplete="new-password"
                        placeholder={t(
                            'ui.settings.security.password.confirm_password_placeholder',
                        )}
                        passwordrules={passwordRules}
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <div class="pt-2">
                    <Button
                        type="submit"
                        class="h-11 rounded-xl px-6 text-sm font-semibold shadow-xs transition-all active:scale-[0.99]"
                        disabled={processing}
                        data-test="update-password-button"
                    >
                        {#if processing}
                            <Spinner class="size-4" />
                        {/if}
                        {t('ui.settings.security.password.save')}
                    </Button>
                </div>
            {/snippet}
        </Form>
    </div>

    <!-- 2FA Management -->
    <ManageTwoFactor
        {canManageTwoFactor}
        {requiresConfirmation}
        {twoFactorEnabled}
    />

    <!-- Passkeys Management -->
    <ManagePasskeys {canManagePasskeys} {passkeys} />
</div>
