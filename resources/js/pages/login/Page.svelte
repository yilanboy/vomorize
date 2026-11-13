<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasskeyVerify from '@/components/PasskeyVerify.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
    import { register } from '@/routes';
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';

    let {
        status = '',
        canResetPassword,
    }: {
        status?: string;
        canResetPassword: boolean;
    } = $props();

    setLayoutProps({
        title: t('ui.auth.login.title'),
        description: t('ui.auth.login.subtitle'),
    });
</script>

<AppHead title={t('ui.auth.login.title')} />

<div class="space-y-6">
    {#if status}
        <div
            class="rounded-xl border border-emerald-500/20 bg-emerald-50/80 p-3.5 text-center text-sm font-medium text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-300"
        >
            {status}
        </div>
    {/if}

    <!-- Passkey Quick Login (prominently placed at top when supported) -->
    <PasskeyVerify
        label={t('ui.auth.login.passkey_button')}
        loadingLabel={t('ui.auth.login.passkey_loading')}
        showSeparator={true}
        separatorPosition="after"
        separator={t('ui.auth.or_email')}
        class="border-border/80 hover:bg-accent/60 h-12 rounded-xl text-sm font-medium transition-all active:scale-[0.99]"
    />

    <!-- Credentials Form -->
    <Form
        {...store.form()}
        resetOnSuccess={['password']}
        class="flex flex-col gap-4"
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-1.5">
                <Label
                    for="email"
                    class="text-foreground text-sm font-semibold"
                >
                    {t('ui.auth.login.email')}
                </Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder={t('ui.auth.login.email_placeholder')}
                    class="h-12 rounded-xl text-sm"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-1.5">
                <div class="flex items-center justify-between">
                    <Label
                        for="password"
                        class="text-foreground text-sm font-semibold"
                    >
                        {t('ui.auth.login.password')}
                    </Label>
                    {#if canResetPassword}
                        <TextLink
                            href={request()}
                            class="text-primary text-sm font-medium transition-colors hover:underline"
                        >
                            {t('ui.auth.login.forgot_password')}
                        </TextLink>
                    {/if}
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder={t('ui.auth.login.password_placeholder')}
                    class="h-12 rounded-xl text-sm"
                />
                <InputError message={errors.password} />
            </div>

            <div class="flex items-center space-x-2 pt-0.5">
                <Checkbox id="remember" name="remember" />
                <Label
                    for="remember"
                    class="text-muted-foreground hover:text-foreground cursor-pointer text-sm font-normal select-none"
                >
                    {t('ui.auth.login.remember_me')}
                </Label>
            </div>

            <Button
                type="submit"
                class="bg-primary text-primary-foreground hover:bg-primary/90 mt-1 flex h-12 w-full items-center justify-center gap-2 rounded-xl text-sm font-semibold shadow-xs transition-all active:scale-[0.99]"
                disabled={processing}
                data-test="login-button"
            >
                {#if processing}
                    <Spinner class="size-4" />
                {/if}
                <span>{t('ui.auth.login.submit')}</span>
                <ArrowRight class="size-4" />
            </Button>
        {/snippet}
    </Form>

    <!-- Card Footer: 還沒有帳號？立即免費註冊 -->
    <div class="border-border/60 mt-6 border-t pt-5 text-center text-sm">
        <span class="text-muted-foreground"
            >{t('ui.auth.login.no_account')}</span
        >
        <TextLink
            href={register()}
            class="text-primary ml-1.5 font-semibold transition-colors hover:underline"
        >
            {t('ui.auth.login.sign_up')}
        </TextLink>
    </div>
</div>
