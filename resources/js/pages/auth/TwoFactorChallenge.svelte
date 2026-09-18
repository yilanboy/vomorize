<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import {
        InputOTP,
        InputOTPGroup,
        InputOTPSlot,
    } from '@/components/ui/input-otp';
    import { t } from '@/lib/i18n';
    import { store } from '@/routes/two-factor/login';
    import type { TwoFactorConfigContent } from '@/types';

    let showRecoveryInput = $state(false);
    let code = $state('');

    const authConfigContent: TwoFactorConfigContent = $derived.by(() => {
        if (showRecoveryInput) {
            return {
                title: t('ui.auth.two_factor.recovery_code_title'),
                description: t('ui.auth.two_factor.recovery_code_subtitle'),
                buttonText: t('ui.auth.two_factor.switch_to_auth_code'),
            };
        }

        return {
            title: t('ui.auth.two_factor.auth_code_title'),
            description: t('ui.auth.two_factor.auth_code_subtitle'),
            buttonText: t('ui.auth.two_factor.switch_to_recovery'),
        };
    });

    $effect(() => {
        setLayoutProps({
            title: authConfigContent.title,
            description: authConfigContent.description,
        });
    });

    function toggleRecoveryMode(clearErrors: () => void) {
        showRecoveryInput = !showRecoveryInput;
        clearErrors();
        code = '';
    }
</script>

<AppHead title={t('ui.auth.two_factor.title')} />

<div class="space-y-6">
    {#if !showRecoveryInput}
        <Form
            {...store.form()}
            class="space-y-4"
            resetOnError
            onError={() => (code = '')}
        >
            {#snippet children({ errors, processing, clearErrors })}
                <input type="hidden" name="code" value={code} />
                <div
                    class="flex flex-col items-center justify-center space-y-3 text-center"
                >
                    <div class="flex w-full items-center justify-center">
                        <InputOTP
                            id="otp"
                            bind:value={code}
                            maxlength={6}
                            disabled={processing}
                            autofocus
                        >
                            <InputOTPGroup>
                                {#each { length: 6 } as _, i (i)}
                                    <InputOTPSlot index={i} />
                                {/each}
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <InputError message={errors.code} />
                </div>
                <Button
                    type="submit"
                    class="h-11 w-full rounded-xl font-semibold shadow-xs"
                    disabled={processing}
                >
                    {t('ui.auth.two_factor.submit')}
                </Button>
                <div class="text-muted-foreground text-center text-sm">
                    <span>{t('ui.auth.two_factor.or_you_can')} </span>
                    <button
                        type="button"
                        class="text-foreground underline decoration-zinc-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-zinc-500"
                        onclick={() => toggleRecoveryMode(clearErrors)}
                    >
                        {authConfigContent.buttonText}
                    </button>
                </div>
            {/snippet}
        </Form>
    {:else}
        <Form {...store.form()} class="space-y-4" resetOnError>
            {#snippet children({ errors, processing, clearErrors })}
                <Input
                    name="recovery_code"
                    type="text"
                    placeholder={t('ui.auth.two_factor.recovery_placeholder')}
                    required
                    class="h-11 rounded-xl"
                />
                <InputError message={errors.recovery_code} />
                <Button
                    type="submit"
                    class="h-11 w-full rounded-xl font-semibold shadow-xs"
                    disabled={processing}
                >
                    {t('ui.auth.two_factor.submit')}
                </Button>

                <div class="text-muted-foreground text-center text-sm">
                    <span>{t('ui.auth.two_factor.or_you_can')} </span>
                    <button
                        type="button"
                        class="text-foreground underline decoration-zinc-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-zinc-500"
                        onclick={() => toggleRecoveryMode(clearErrors)}
                    >
                        {authConfigContent.buttonText}
                    </button>
                </div>
            {/snippet}
        </Form>
    {/if}
</div>
