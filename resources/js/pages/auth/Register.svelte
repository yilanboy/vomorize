<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
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
    class="flex flex-col gap-4"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-1.5">
            <Label for="name" class="text-foreground text-sm font-semibold">
                {t('ui.auth.register.name')}
            </Label>
            <Input
                id="name"
                type="text"
                required
                autocomplete="name"
                name="name"
                placeholder={t('ui.auth.register.name_placeholder')}
                class="h-12 rounded-xl text-sm"
            />
            <InputError message={errors.name} />
        </div>

        <div class="grid gap-1.5">
            <Label for="email" class="text-foreground text-sm font-semibold">
                {t('ui.auth.register.email')}
            </Label>
            <Input
                id="email"
                type="email"
                required
                autocomplete="email"
                name="email"
                placeholder={t('ui.auth.register.email_placeholder')}
                class="h-12 rounded-xl text-sm"
            />
            <InputError message={errors.email} />
        </div>

        <div class="grid gap-1.5">
            <Label for="password" class="text-foreground text-sm font-semibold">
                {t('ui.auth.register.password')}
            </Label>
            <PasswordInput
                id="password"
                required
                autocomplete="new-password"
                name="password"
                placeholder={t('ui.auth.register.password_placeholder')}
                passwordrules={passwordRules}
                class="h-12 rounded-xl text-sm"
            />
            <InputError message={errors.password} />
        </div>

        <div class="grid gap-1.5">
            <Label
                for="password_confirmation"
                class="text-foreground text-sm font-semibold"
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
                class="h-12 rounded-xl text-sm"
            />
            <InputError message={errors.password_confirmation} />
        </div>

        <div
            class="text-muted-foreground flex items-start gap-2 pt-0.5 pb-0.5 text-sm"
        >
            <ShieldCheck
                class="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-500"
            />
            <p class="leading-relaxed">
                {t('ui.auth.register.password_hint')}
            </p>
        </div>

        <Button
            type="submit"
            class="bg-primary text-primary-foreground hover:bg-primary/90 mt-1 flex h-12 w-full items-center justify-center gap-2 rounded-xl text-sm font-semibold shadow-xs transition-all active:scale-[0.99]"
            disabled={processing}
            data-test="register-user-button"
        >
            {#if processing}
                <Spinner class="size-4" />
            {/if}
            <span>{t('ui.auth.register.submit')}</span>
            <ArrowRight class="size-4" />
        </Button>

        <div class="border-border/60 mt-6 border-t pt-5 text-center text-sm">
            <span class="text-muted-foreground"
                >{t('ui.auth.register.has_account')}</span
            >
            <TextLink
                href={login()}
                class="text-primary ml-1.5 font-semibold transition-colors hover:underline"
            >
                {t('ui.auth.register.sign_in')}
            </TextLink>
        </div>
    {/snippet}
</Form>
