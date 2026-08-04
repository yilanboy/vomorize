<script module lang="ts">
    export const layout = {
        title: 'register_title',
        description: 'register_subtitle',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { translations } from '@/lib/locale.svelte';
    import { login } from '@/routes';
    import { store } from '@/routes/register';

    let { passwordRules }: { passwordRules: string } = $props();

    let t = $derived(translations());
</script>

<AppHead title={t['register']} />

<Form
    {...store.form()}
    resetOnSuccess={['password', 'password_confirmation']}
    class="flex flex-col gap-6"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-4">
            <div class="grid gap-2.5">
                <Label for="name">{t['name']}</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autocomplete="name"
                    name="name"
                    placeholder={t['full_name']}
                />
                <InputError message={errors.name} />
            </div>

            <div class="grid gap-2.5">
                <Label for="email">{t['email']}</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-2.5">
                <Label for="password">{t['password']}</Label>
                <PasswordInput
                    id="password"
                    required
                    autocomplete="new-password"
                    name="password"
                    placeholder={t['password']}
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password} />
            </div>

            <div class="grid gap-2.5">
                <Label for="password_confirmation">{t['confirm_password']}</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder={t['confirm_password']}
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                disabled={processing}
                data-test="register-user-button"
            >
                {#if processing}<Spinner />{/if}
                {t['create_account']}
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
                        <span>{t['github_register']}</span>
                    </a>
                {/snippet}
            </Button>
        </div>

        <div class="text-center text-sm text-zinc-500 dark:text-zinc-400">
            {t['already_have_account']}
            <TextLink href={login()}>{t['login']}</TextLink>
        </div>
    {/snippet}
</Form>
