<script module lang="ts">
    export const layout = {
        title: 'confirm_password_title',
        description: 'confirm_password_description',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import {
        index as confirmOptions,
        store as confirmStore,
    } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasskeyVerify from '@/components/PasskeyVerify.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { translations } from '@/lib/locale.svelte';
    import { store } from '@/routes/password/confirm';

    let t = $derived(translations());
</script>

<AppHead title={t['confirm_password_title']} />

<PasskeyVerify
    routes={{
        options: confirmOptions(),
        submit: confirmStore(),
    }}
    label={t['confirm_with_passkey']}
    loadingLabel={t['confirming']}
    separator={t['or_confirm_with_password']}
/>

<Form {...store.form()} resetOnSuccess>
    {#snippet children({ errors, processing })}
        <div class="space-y-6">
            <div class="grid gap-2.5">
                <Label for="password">{t['password']}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder={t['password']}
                />
                <InputError message={errors.password} />
            </div>

            <div class="flex items-center">
                <Button
                    type="submit"
                    class="w-full"
                    disabled={processing}
                    data-test="confirm-password-button"
                >
                    {#if processing}<Spinner />{/if}
                    {processing
                        ? t['confirming']
                        : t['confirm_password_button']}
                </Button>
            </div>
        </div>
    {/snippet}
</Form>
