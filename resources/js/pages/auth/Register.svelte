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
    import { store } from '@/routes/register';

    let { passwordRules }: { passwordRules: string } = $props();

    setLayoutProps({
        title: t('ui.auth.register.title'),
        description: t('ui.auth.register.subtitle'),
    });
</script>

<AppHead title={t('ui.auth.register.title')} />

<Form
    {...store.form()}
    resetOnSuccess={['password', 'password_confirmation']}
    class="flex flex-col gap-5"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-4">
            <div class="grid gap-1.5">
                <Label for="name" class="text-foreground text-xs font-semibold">
                    {t('ui.auth.register.name')}
                </Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autocomplete="name"
                    name="name"
                    placeholder={t('ui.auth.register.name_placeholder')}
                    class="h-11 rounded-xl"
                />
                <InputError message={errors.name} />
            </div>

            <div class="grid gap-1.5">
                <Label
                    for="email"
                    class="text-foreground text-xs font-semibold"
                >
                    {t('ui.auth.register.email')}
                </Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autocomplete="email"
                    name="email"
                    placeholder={t('ui.auth.register.email_placeholder')}
                    class="h-11 rounded-xl"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-1.5">
                <Label
                    for="password"
                    class="text-foreground text-xs font-semibold"
                >
                    {t('ui.auth.register.password')}
                </Label>
                <PasswordInput
                    id="password"
                    required
                    autocomplete="new-password"
                    name="password"
                    placeholder={t('ui.auth.register.password_placeholder')}
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
                    {t('ui.auth.register.password_confirmation')}
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder={t(
                        'ui.auth.register.password_confirmation_placeholder',
                    )}
                    passwordrules={passwordRules}
                    class="h-11 rounded-xl"
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <p class="text-muted-foreground text-xs leading-relaxed">
                {t('ui.auth.register.password_hint')}
            </p>

            <Button
                type="submit"
                class="mt-1 h-11 w-full rounded-xl font-semibold shadow-xs"
                disabled={processing}
                data-test="register-user-button"
            >
                {#if processing}<Spinner class="mr-2" />{/if}
                {t('ui.auth.register.submit')}
            </Button>
        </div>

        <div class=" text-muted-foreground pt-3 text-center text-sm">
            <span>{t('ui.auth.register.has_account')}</span>
            <TextLink
                href={login()}
                class="text-primary ml-1 font-semibold hover:underline"
            >
                {t('ui.auth.register.sign_in')}
            </TextLink>
        </div>
    {/snippet}
</Form>
