<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import Eye from '@lucide/svelte/icons/eye';
    import EyeOff from '@lucide/svelte/icons/eye-off';
    import LockKeyhole from '@lucide/svelte/icons/lock-keyhole';
    import RefreshCw from '@lucide/svelte/icons/refresh-cw';
    import { onMount, tick } from 'svelte';
    import AlertError from '@/components/AlertError.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
    import { twoFactorAuthState } from '@/lib/twoFactorAuth.svelte';
    import { regenerateRecoveryCodes } from '@/routes/two-factor';

    const twoFactorAuth = twoFactorAuthState();
    let isRecoveryCodesVisible = $state(false);
    let recoveryCodeSectionRef = $state<HTMLDivElement | undefined>();

    async function toggleRecoveryCodesVisibility() {
        if (
            !isRecoveryCodesVisible &&
            !twoFactorAuth.state.recoveryCodesList.length
        ) {
            await twoFactorAuth.fetchRecoveryCodes();
        }

        isRecoveryCodesVisible = !isRecoveryCodesVisible;

        if (isRecoveryCodesVisible) {
            await tick();
            recoveryCodeSectionRef?.scrollIntoView({ behavior: 'smooth' });
        }
    }

    onMount(async () => {
        if (!twoFactorAuth.state.recoveryCodesList.length) {
            await twoFactorAuth.fetchRecoveryCodes();
        }
    });
</script>

<div
    class="border-border/80 bg-muted/20 space-y-4 rounded-xl border p-5 sm:p-6"
>
    <div class="space-y-1">
        <h3
            class="text-foreground flex items-center gap-2 text-base font-semibold"
        >
            <LockKeyhole class="text-primary size-4" />
            <span
                >{t(
                    'ui.settings.security.two_factor.recovery_codes.title',
                )}</span
            >
        </h3>
        <p class="text-muted-foreground text-sm leading-relaxed">
            {t('ui.settings.security.two_factor.recovery_codes.description')}
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5 pt-1">
        <Button
            variant="outline"
            onclick={toggleRecoveryCodesVisibility}
            class="h-10 gap-2 rounded-xl px-4 text-sm font-medium transition-all"
        >
            {#if isRecoveryCodesVisible}
                <EyeOff class="size-4" />
                <span
                    >{t(
                        'ui.settings.security.two_factor.recovery_codes.hide',
                    )}</span
                >
            {:else}
                <Eye class="size-4" />
                <span
                    >{t(
                        'ui.settings.security.two_factor.recovery_codes.view',
                    )}</span
                >
            {/if}
        </Button>

        {#if isRecoveryCodesVisible && twoFactorAuth.state.recoveryCodesList.length}
            <Form
                {...regenerateRecoveryCodes.form()}
                options={{ preserveScroll: true }}
                onSuccess={() => twoFactorAuth.fetchRecoveryCodes()}
            >
                {#snippet children({ processing })}
                    <Button
                        variant="secondary"
                        type="submit"
                        disabled={processing}
                        class="h-10 gap-2 rounded-xl px-4 text-sm font-medium transition-all"
                    >
                        {#if processing}
                            <Spinner class="size-4" />
                        {:else}
                            <RefreshCw class="size-4" />
                        {/if}
                        <span
                            >{t(
                                'ui.settings.security.two_factor.recovery_codes.regenerate',
                            )}</span
                        >
                    </Button>
                {/snippet}
            </Form>
        {/if}
    </div>

    <div
        class={{
            'relative overflow-hidden transition-all duration-300': true,
            'h-auto opacity-100': isRecoveryCodesVisible,
            'h-0 opacity-0': !isRecoveryCodesVisible,
        }}
    >
        {#if twoFactorAuth.state.errors.length}
            <div class="mt-4">
                <AlertError errors={twoFactorAuth.state.errors} />
            </div>
        {:else}
            <div class="mt-3 space-y-3">
                <div
                    bind:this={recoveryCodeSectionRef}
                    class="border-border/80 bg-background grid grid-cols-1 gap-2 rounded-xl border p-4 text-center font-mono text-sm tracking-wider sm:grid-cols-2"
                >
                    {#if !twoFactorAuth.state.recoveryCodesList.length}
                        <div class="col-span-2 space-y-2">
                            {#each { length: 8 } as _, n (n)}
                                <div
                                    class="bg-muted-foreground/20 h-5 w-full animate-pulse rounded"
                                ></div>
                            {/each}
                        </div>
                    {:else}
                        {#each twoFactorAuth.state.recoveryCodesList as code, index (index)}
                            <div
                                class="bg-muted/40 text-foreground rounded-lg px-3 py-2 font-semibold"
                            >
                                {code}
                            </div>
                        {/each}
                    {/if}
                </div>
                <p
                    class="text-muted-foreground text-sm leading-relaxed select-none"
                >
                    {t('ui.settings.security.two_factor.recovery_codes.notice')}
                </p>
            </div>
        {/if}
    </div>
</div>
