<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
    import { login } from '@/routes';
    import { update } from '@/routes/password';

    let {
        token,
        email,
        passwordRules,
    }: {
        token: string;
        email: string;
        passwordRules: string;
    } = $props();

    setLayoutProps({
        title: t('ui.auth.reset_password.title'),
        description: t('ui.auth.reset_password.subtitle'),
    });
</script>

<AppHead title={t('ui.auth.reset_password.title')} />

<Form
    {...update.form()}
    transform={(data) => ({ ...data, token, email })}
    resetOnSuccess={['password', 'password_confirmation']}
    class="flex flex-col gap-5"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-4">
            <div class="grid gap-1.5">
                <Label
                    for="email"
                    class="text-foreground text-xs font-semibold"
                >
                    {t('ui.auth.reset_password.email')}
                </Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    value={email}
                    readonly
                    class="bg-muted/50 text-muted-foreground h-11 cursor-not-allowed rounded-xl"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-1.5">
                <Label
                    for="password"
                    class="text-foreground text-xs font-semibold"
                >
                    {t('ui.auth.reset_password.password')}
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder={t(
                        'ui.auth.reset_password.password_placeholder',
                    )}
                    passwordrules={passwordRules}
                    class="h-11 rounded-xl"
                />
                <InputError message={errors.password} />
            </div>

            <div class="grid gap-1.5">
                <Label
                    for="password_confirmation"
                    class="text-foreground text-xs font-semibold"
                >
                    {t('ui.auth.reset_password.password_confirmation')}
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder={t(
                        'ui.auth.reset_password.password_confirmation_placeholder',
                    )}
                    passwordrules={passwordRules}
                    class="h-11 rounded-xl"
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <Button
                type="submit"
                class="mt-1 h-11 w-full rounded-xl font-semibold shadow-xs"
                disabled={processing}
                data-test="reset-password-button"
            >
                {#if processing}<Spinner class="mr-2" />{/if}
                {t('ui.auth.reset_password.submit')}
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
