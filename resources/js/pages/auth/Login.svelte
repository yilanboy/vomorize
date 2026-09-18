<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Github from '@/components/icons/Github.svelte';
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

<div class="space-y-5">
    {#if status}
        <div
            class="rounded-xl border border-emerald-500/20 bg-emerald-50/50 p-3 text-center text-sm font-medium text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400"
        >
            {status}
        </div>
    {/if}

    <Form
        {...store.form()}
        resetOnSuccess={['password']}
        class="flex flex-col gap-5"
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-4">
                <div class="grid gap-1.5">
                    <Label
                        for="email"
                        class="text-foreground text-xs font-semibold"
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
                        class="h-11 rounded-xl"
                    />
                    <InputError message={errors.email} />
                </div>

                <div class="grid gap-1.5">
                    <div class="flex items-center justify-between">
                        <Label
                            for="password"
                            class="text-foreground text-xs font-semibold"
                        >
                            {t('ui.auth.login.password')}
                        </Label>
                        {#if canResetPassword}
                            <TextLink
                                href={request()}
                                class="text-primary text-xs font-medium hover:underline"
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
                        class="h-11 rounded-xl"
                    />
                    <InputError message={errors.password} />
                </div>

                <div class="flex items-center space-x-2 pt-0.5">
                    <Checkbox id="remember" name="remember" />
                    <Label
                        for="remember"
                        class="text-muted-foreground cursor-pointer text-sm font-normal select-none"
                    >
                        {t('ui.auth.login.remember_me')}
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-1 h-11 w-full rounded-xl font-semibold shadow-xs"
                    disabled={processing}
                    data-test="login-button"
                >
                    {#if processing}<Spinner class="mr-2" />{/if}
                    {t('ui.auth.login.submit')}
                </Button>
            </div>
        {/snippet}
    </Form>

    <!-- Divider: 或使用其他方式繼續 -->
    <div class="relative my-6 flex items-center justify-center">
        <div class="border-border/60 w-full border-t"></div>
        <span
            class="bg-card text-muted-foreground absolute px-3 text-xs font-medium"
        >
            {t('ui.auth.login.or_alternative')}
        </span>
    </div>

    <!-- Alternative login actions (GitHub + Passkey) -->
    <div class="grid gap-2.5">
        <PasskeyVerify
            label={t('ui.auth.login.passkey_button')}
            loadingLabel={t('ui.auth.login.passkey_loading')}
            showSeparator={false}
            class="border-border/70 hover:bg-accent/60 h-11 rounded-xl font-medium transition-colors"
        />

        <Button
            variant="outline"
            class="border-border/70 hover:bg-accent/60 h-11 w-full rounded-xl font-medium transition-colors"
            asChild
        >
            {#snippet children(props)}
                <a href="/auth/github/redirect" class={props.class}>
                    <Github class="size-4" />
                    <span>{t('ui.auth.login.github_button')}</span>
                </a>
            {/snippet}
        </Button>
    </div>

    <!-- Footer: 還沒有帳號？立即免費註冊 -->
    <div class="text-muted-foreground pt-3 text-center text-sm">
        <span>{t('ui.auth.login.no_account')}</span>
        <TextLink
            href={register()}
            class="text-primary ml-1 font-semibold hover:underline"
        >
            {t('ui.auth.login.sign_up')}
        </TextLink>
    </div>
</div>
