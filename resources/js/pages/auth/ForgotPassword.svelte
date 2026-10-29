<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
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

<div class="space-y-6">
    {#if status}
        <div
            class="rounded-xl border border-emerald-500/20 bg-emerald-50/80 p-3.5 text-center text-sm font-medium text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-300"
        >
            {status}
        </div>
    {/if}

    <Form {...email.form()} class="flex flex-col gap-4">
        {#snippet children({ errors, processing })}
            <div class="grid gap-1.5">
                <Label
                    for="email"
                    class="text-foreground text-sm font-semibold"
                >
                    {t('ui.auth.forgot_password.email')}
                </Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder={t('ui.auth.forgot_password.email_placeholder')}
                    class="h-12 rounded-xl text-sm"
                />
                <InputError message={errors.email} />
            </div>

            <Button
                type="submit"
                class="bg-primary text-primary-foreground hover:bg-primary/90 mt-1 flex h-12 w-full items-center justify-center gap-2 rounded-xl text-sm font-semibold shadow-xs transition-all active:scale-[0.99]"
                disabled={processing}
                data-test="email-password-reset-link-button"
            >
                {#if processing}<Spinner class="size-4" />{/if}
                <span>{t('ui.auth.forgot_password.submit')}</span>
                <ArrowRight class="size-4" />
            </Button>

            <div
                class="border-border/60 mt-6 border-t pt-5 text-center text-sm"
            >
                <span class="text-muted-foreground"
                    >{t('ui.auth.forgot_password.or_return_to')}</span
                >
                <TextLink
                    href={login()}
                    class="text-primary ml-1 font-semibold transition-colors hover:underline"
                >
                    {t('ui.auth.forgot_password.back_to_login')}
                </TextLink>
            </div>
        {/snippet}
    </Form>
</div>
