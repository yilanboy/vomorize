<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
    import { onDestroy } from 'svelte';
    import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.svelte';
    import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
    import { twoFactorAuthState } from '@/lib/twoFactorAuth.svelte';
    import { disable, enable } from '@/routes/two-factor';

    export type Props = {
        canManageTwoFactor?: boolean;
        requiresConfirmation?: boolean;
        twoFactorEnabled?: boolean;
    };

    let {
        canManageTwoFactor = false,
        requiresConfirmation = false,
        twoFactorEnabled = false,
    }: Props = $props();

    const twoFactorAuth = twoFactorAuthState();
    let showSetupModal = $state(false);

    onDestroy(() => twoFactorAuth.clearTwoFactorAuthData());
</script>

{#if canManageTwoFactor}
    <div
        class="border-border/80 bg-card space-y-6 rounded-2xl border p-6 shadow-xs sm:p-7"
    >
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="flex items-start gap-3">
                <div
                    class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl"
                >
                    <ShieldCheck class="size-5" />
                </div>
                <div>
                    <h2
                        class="text-foreground text-lg font-semibold tracking-tight"
                    >
                        {t('ui.settings.security.two_factor.title')}
                    </h2>
                    <p class="text-muted-foreground mt-0.5 text-sm">
                        {t('ui.settings.security.two_factor.subtitle')}
                    </p>
                </div>
            </div>

            <div>
                {#if twoFactorEnabled}
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-50/80 px-3 py-1 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-300"
                    >
                        <span
                            class="size-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"
                        ></span>
                        {t('ui.settings.security.two_factor.status_enabled')}
                    </span>
                {:else}
                    <span
                        class="border-border bg-muted/60 text-muted-foreground inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-medium"
                    >
                        <span
                            class="bg-muted-foreground/50 size-1.5 rounded-full"
                        ></span>
                        {t('ui.settings.security.two_factor.status_disabled')}
                    </span>
                {/if}
            </div>
        </header>

        {#if !twoFactorEnabled}
            <div class="space-y-4">
                <p class="text-muted-foreground text-sm leading-relaxed">
                    {t('ui.settings.security.two_factor.description')}
                </p>

                <div>
                    {#if twoFactorAuth.hasSetupData()}
                        <Button
                            class="h-11 gap-2 rounded-xl px-5 text-sm font-semibold shadow-xs transition-all active:scale-[0.99]"
                            onclick={() => (showSetupModal = true)}
                        >
                            <ShieldCheck class="size-4" />
                            {t(
                                'ui.settings.security.two_factor.continue_setup',
                            )}
                        </Button>
                    {:else}
                        <Form
                            {...enable.form()}
                            onSuccess={() => (showSetupModal = true)}
                        >
                            {#snippet children({ processing })}
                                <Button
                                    type="submit"
                                    class="h-11 rounded-xl px-5 text-sm font-semibold shadow-xs transition-all active:scale-[0.99]"
                                    disabled={processing}
                                >
                                    {#if processing}
                                        <Spinner class="size-4" />
                                    {/if}
                                    {t(
                                        'ui.settings.security.two_factor.enable_button',
                                    )}
                                </Button>
                            {/snippet}
                        </Form>
                    {/if}
                </div>
            </div>
        {:else}
            <div class="space-y-6">
                <p class="text-muted-foreground text-sm leading-relaxed">
                    {t('ui.settings.security.two_factor.enabled_description')}
                </p>

                <div>
                    <Form {...disable.form()}>
                        {#snippet children({ processing })}
                            <Button
                                variant="outline"
                                type="submit"
                                class="text-destructive hover:bg-destructive/10 hover:text-destructive border-destructive/30 h-11 rounded-xl px-5 text-sm font-medium transition-all"
                                disabled={processing}
                            >
                                {#if processing}
                                    <Spinner class="size-4" />
                                {/if}
                                {t(
                                    'ui.settings.security.two_factor.disable_button',
                                )}
                            </Button>
                        {/snippet}
                    </Form>
                </div>

                <TwoFactorRecoveryCodes />
            </div>
        {/if}

        <TwoFactorSetupModal
            bind:isOpen={showSetupModal}
            {requiresConfirmation}
            {twoFactorEnabled}
        />
    </div>
{/if}
