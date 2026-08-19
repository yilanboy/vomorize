<script module lang="ts">
    import { translations } from '@/lib/locale.svelte';
    import { edit } from '@/routes/security';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import ManagePasskeys from '@/components/ManagePasskeys.svelte';
    import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.svelte';
    import ManageTwoFactor from '@/components/ManageTwoFactor.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';

    const canManageTwoFactor = $derived(Boolean(page.props.canManageTwoFactor));
    const requiresConfirmation = $derived(Boolean(page.props.requiresConfirmation));
    const twoFactorEnabled = $derived(Boolean(page.props.twoFactorEnabled));
    const canManagePasskeys = $derived(Boolean(page.props.canManagePasskeys));
    const passkeys = $derived(
        (Array.isArray(page.props.passkeys)
            ? page.props.passkeys
            : []) as ManagePasskeysProps['passkeys'],
    );

    let t = $derived(translations());
    let { passwordRules }: { passwordRules: string } = $props();
</script>

<AppHead title={t['security_settings']} />

<div class="space-y-6">
    <div
        class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs sm:p-8 dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="mb-6 space-y-1">
            <h2 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-50">
                {t['update_password']}
            </h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {t['update_password_subtitle']}
            </p>
        </div>

        <Form
            {...SecurityController.update.form()}
            class="space-y-5"
            options={{ preserveScroll: true }}
            resetOnSuccess
            resetOnError={['password', 'password_confirmation', 'current_password']}
        >
            {#snippet children({ errors, processing })}
                <div class="grid gap-2.5">
                    <Label for="current_password">{t['current_password']}</Label>
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        placeholder={t['enter_current_password']}
                    />
                    <InputError message={errors.current_password} />
                </div>

                <div class="grid gap-2.5">
                    <Label for="password">{t['new_password']}</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        placeholder={t['enter_new_password']}
                        passwordrules={passwordRules}
                    />
                    <InputError message={errors.password} />
                </div>

                <div class="grid gap-2.5">
                    <Label for="password_confirmation">{t['confirm_new_password']}</Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        placeholder={t['enter_confirm_new_password']}
                        passwordrules={passwordRules}
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <div class="pt-2">
                    <Button type="submit" disabled={processing} data-test="update-password-button">
                        {processing ? t['updating'] : t['save_password']}
                    </Button>
                </div>
            {/snippet}
        </Form>
    </div>

    <ManageTwoFactor {canManageTwoFactor} {requiresConfirmation} {twoFactorEnabled} />

    <ManagePasskeys {canManagePasskeys} {passkeys} />
</div>
