<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
    import { onDestroy } from 'svelte';
    import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.svelte';
    import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.svelte';
    import { Button } from '@/components/ui/button';
    import { translations } from '@/lib/locale.svelte';
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
    let t = $derived(translations());

    onDestroy(() => twoFactorAuth.clearTwoFactorAuthData());
</script>

{#if canManageTwoFactor}
    <div
        class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs sm:p-8 dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="mb-6 space-y-1">
            <h2
                class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-50"
            >
                {t['two_factor_auth']}
            </h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {t['two_factor_subtitle']}
            </p>
        </div>

        {#if !twoFactorEnabled}
            <div class="space-y-4">
                <p
                    class="text-sm leading-relaxed text-zinc-500 dark:text-zinc-400"
                >
                    {t['two_factor_disabled_desc']}
                </p>

                <div>
                    {#if twoFactorAuth.hasSetupData()}
                        <Button onclick={() => (showSetupModal = true)}>
                            <ShieldCheck class="h-4 w-4" />
                            {t['continue_two_factor_setup']}
                        </Button>
                    {:else}
                        <Form
                            {...enable.form()}
                            onSuccess={() => (showSetupModal = true)}
                        >
                            {#snippet children({ processing })}
                                <Button type="submit" disabled={processing}>
                                    {t['enable_two_factor']}
                                </Button>
                            {/snippet}
                        </Form>
                    {/if}
                </div>
            </div>
        {:else}
            <div class="space-y-4">
                <p
                    class="text-sm leading-relaxed text-zinc-500 dark:text-zinc-400"
                >
                    {t['two_factor_enabled_desc']}
                </p>

                <div class="pt-1">
                    <Form {...disable.form()}>
                        {#snippet children({ processing })}
                            <Button
                                variant="destructive"
                                type="submit"
                                disabled={processing}
                            >
                                {t['disable_two_factor']}
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
