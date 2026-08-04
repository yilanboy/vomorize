<script module lang="ts">
    export const layout = {
        title: 'login_title',
        description: 'login_subtitle',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { translations } from '@/lib/locale.svelte';
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

    let t = $derived(translations());
</script>

<AppHead title={t['login']} />

{#if status}
    <div class="mb-4 text-center text-sm font-medium text-green-600">
        {status}
    </div>
{/if}

<Form {...store.form()} resetOnSuccess={['password']} class="flex flex-col gap-6">
    {#snippet children({ errors, processing })}
        <div class="grid gap-4">
            <div class="grid gap-2.5">
                <Label for="email">{t['email']}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-2.5">
                <div class="flex items-center justify-between">
                    <Label for="password">{t['password']}</Label>
                    {#if canResetPassword}
                        <TextLink href={request()} class="text-sm"
                            >{t['forgot_password']}</TextLink
                        >
                    {/if}
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder={t['password']}
                />
                <InputError message={errors.password} />
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <Checkbox id="remember" name="remember" />
                    <Label for="remember" class="cursor-pointer select-none">
                        {t['remember_me']}
                    </Label>
                </div>
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                disabled={processing}
                data-test="login-button"
            >
                {#if processing}<Spinner />{/if}
                {t['login']}
            </Button>

            <div class="relative py-2">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t border-zinc-200 dark:border-zinc-800"></span>
                </div>
                <div class="relative flex justify-center text-sm font-medium uppercase">
                    <span class="bg-zinc-50 px-2 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">{t['or']}</span>
                </div>
            </div>

            <Button variant="outline" class="w-full" asChild>
                {#snippet children(props)}
                    <a href="/auth/github" {...props}>
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.39-1.305.705-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"
                            />
                        </svg>
                        <span>{t['github_login']}</span>
                    </a>
                {/snippet}
            </Button>
        </div>

        <div class="text-center text-sm text-zinc-500 dark:text-zinc-400">
            {t['no_account']}
            <TextLink href={register()}>{t['sign_up']}</TextLink>
        </div>
    {/snippet}
</Form>
