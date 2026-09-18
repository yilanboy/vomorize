<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import Copy from '@lucide/svelte/icons/copy';
    import ScanLine from '@lucide/svelte/icons/scan-line';
    import { tick } from 'svelte';
    import AlertError from '@/components/AlertError.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogTitle,
    } from '@/components/ui/dialog';
    import {
        InputOTP,
        InputOTPGroup,
        InputOTPSlot,
    } from '@/components/ui/input-otp';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
    import { themeState } from '@/lib/theme.svelte';
    import { twoFactorAuthState } from '@/lib/twoFactorAuth.svelte';
    import { confirm } from '@/routes/two-factor';
    import type { TwoFactorConfigContent } from '@/types';

    let {
        requiresConfirmation,
        twoFactorEnabled,
        isOpen = $bindable(false),
    }: {
        requiresConfirmation: boolean;
        twoFactorEnabled: boolean;
        isOpen?: boolean;
    } = $props();

    const { resolvedAppearance } = themeState();
    const twoFactorAuth = twoFactorAuthState();

    let showVerificationStep = $state(false);
    let code = $state('');
    let copied = $state(false);
    let pinInputContainerRef = $state<HTMLDivElement>();

    const modalConfig: TwoFactorConfigContent = $derived.by(() => {
        if (twoFactorEnabled) {
            return {
                title: t(
                    'ui.settings.security.two_factor.modal.enabled_title',
                ),
                description: t(
                    'ui.settings.security.two_factor.modal.enabled_desc',
                ),
                buttonText: t(
                    'ui.settings.security.two_factor.modal.close',
                ),
            };
        }

        if (showVerificationStep) {
            return {
                title: t(
                    'ui.settings.security.two_factor.modal.verify_title',
                ),
                description: t(
                    'ui.settings.security.two_factor.modal.verify_desc',
                ),
                buttonText: t(
                    'ui.settings.security.two_factor.modal.continue',
                ),
            };
        }

        return {
            title: t(
                'ui.settings.security.two_factor.modal.setup_title',
            ),
            description: t(
                'ui.settings.security.two_factor.modal.setup_desc',
            ),
            buttonText: t(
                'ui.settings.security.two_factor.modal.continue',
            ),
        };
    });

    const qrCodeDataUrl = $derived.by(() => {
        const qrCodeSvg = twoFactorAuth.state.qrCodeSvg;

        if (!qrCodeSvg) {
            return '';
        }

        return `data:image/svg+xml;utf8,${encodeURIComponent(qrCodeSvg)}`;
    });

    async function copyToClipboard(text: string) {
        await navigator.clipboard.writeText(text);
        copied = true;
        setTimeout(() => (copied = false), 2000);
    }

    async function handleModalNextStep() {
        if (requiresConfirmation) {
            showVerificationStep = true;
            await tick();
            pinInputContainerRef?.querySelector('input')?.focus();

            return;
        }

        twoFactorAuth.clearSetupData();
        isOpen = false;
    }

    function resetModalState() {
        if (twoFactorEnabled) {
            twoFactorAuth.clearSetupData();
        }

        showVerificationStep = false;
        code = '';
    }

    $effect(() => {
        if (!isOpen) {
            resetModalState();

            return;
        }

        if (!twoFactorAuth.state.qrCodeSvg) {
            twoFactorAuth.fetchSetupData();
        }
    });
</script>

<Dialog bind:open={isOpen}>
    <DialogContent class="sm:max-w-md">
        <div class="flex flex-col items-center justify-center">
            <div
                class="border-border bg-card mb-3 w-auto rounded-full border p-0.5 shadow-sm"
            >
                <div
                    class="border-border bg-muted relative overflow-hidden rounded-full border p-2.5"
                >
                    <div class="absolute inset-0 grid grid-cols-5 opacity-50">
                        {#each { length: 5 } as _, i (i)}
                            <div
                                class="border-border border-r last:border-r-0"
                            ></div>
                        {/each}
                    </div>
                    <div class="absolute inset-0 grid grid-rows-5 opacity-50">
                        {#each { length: 5 } as _, i (i)}
                            <div
                                class="border-border border-b last:border-b-0"
                            ></div>
                        {/each}
                    </div>
                    <ScanLine class="text-foreground relative z-20 size-6" />
                </div>
            </div>
            <div class="my-3 space-y-1 text-center">
                <DialogTitle>{modalConfig.title}</DialogTitle>
                <DialogDescription>
                    {modalConfig.description}
                </DialogDescription>
            </div>
        </div>

        <div
            class="relative flex w-auto flex-col items-center justify-center space-y-5"
        >
            {#if !showVerificationStep}
                {#if twoFactorAuth.state.errors.length}
                    <AlertError errors={twoFactorAuth.state.errors} />
                {:else}
                    <div
                        class="relative mx-auto flex max-w-md items-center overflow-hidden"
                    >
                        <div
                            class="border-border relative mx-auto aspect-square w-64 overflow-hidden rounded-lg border"
                        >
                            {#if !twoFactorAuth.state.qrCodeSvg}
                                <div
                                    class="bg-background absolute inset-0 z-10 flex aspect-square h-auto w-full animate-pulse items-center justify-center"
                                >
                                    <Spinner class="size-6" />
                                </div>
                            {:else}
                                <div
                                    class="relative z-10 overflow-hidden border p-5"
                                >
                                    <div
                                        class="flex aspect-square size-full items-center justify-center [&>svg]:size-full"
                                        style={resolvedAppearance() === 'dark'
                                            ? 'filter: invert(1) brightness(1.5)'
                                            : undefined}
                                    >
                                        <img
                                            src={qrCodeDataUrl}
                                            alt={t(
                                                'ui.settings.security.two_factor.modal.qr_alt',
                                            )}
                                            class="size-full"
                                        />
                                    </div>
                                </div>
                            {/if}
                        </div>
                    </div>

                    <div class="flex w-full items-center space-x-5">
                        <Button class="w-full" onclick={handleModalNextStep}>
                            {modalConfig.buttonText}
                        </Button>
                    </div>

                    <div
                        class="relative flex w-full items-center justify-center"
                    >
                        <div
                            class="bg-border absolute inset-0 top-1/2 h-px w-full"
                        ></div>
                        <span class="bg-card relative px-2 py-1">
                            {t(
                                'ui.settings.security.two_factor.modal.or_manual',
                            )}
                        </span>
                    </div>

                    <div
                        class="flex w-full items-center justify-center space-x-2"
                    >
                        <div
                            class="border-border flex w-full items-stretch overflow-hidden rounded-xl border"
                        >
                            {#if !twoFactorAuth.state.manualSetupKey}
                                <div
                                    class="bg-muted flex h-full w-full items-center justify-center p-3"
                                >
                                    <Spinner />
                                </div>
                            {:else}
                                <input
                                    type="text"
                                    readonly
                                    value={twoFactorAuth.state.manualSetupKey}
                                    class="bg-background text-foreground h-full w-full p-3"
                                />
                                <button
                                    onclick={() =>
                                        copyToClipboard(
                                            twoFactorAuth.state
                                                .manualSetupKey || '',
                                        )}
                                    class="border-border hover:bg-muted relative block h-auto border-l px-3"
                                >
                                    {#if copied}
                                        <Check class="w-4 text-green-500" />
                                    {:else}
                                        <Copy class="w-4" />
                                    {/if}
                                </button>
                            {/if}
                        </div>
                    </div>
                {/if}
            {:else}
                <Form
                    {...confirm.form()}
                    resetOnError
                    onFinish={() => (code = '')}
                    onSuccess={() => (isOpen = false)}
                >
                    {#snippet children({ errors: formErrors, processing })}
                        <input type="hidden" name="code" value={code} />
                        <div
                            bind:this={pinInputContainerRef}
                            class="relative w-full space-y-3"
                        >
                            <div
                                class="flex w-full flex-col items-center justify-center space-y-3 py-2"
                            >
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
                                <InputError
                                    message={formErrors?.[
                                        'confirmTwoFactorAuthentication.code'
                                    ]}
                                />
                            </div>

                            <div class="flex w-full items-center space-x-5">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="w-auto flex-1"
                                    onclick={() =>
                                        (showVerificationStep = false)}
                                    disabled={processing}
                                >
                                    {t(
                                        'ui.settings.security.two_factor.modal.back',
                                    )}
                                </Button>
                                <Button
                                    type="submit"
                                    class="w-auto flex-1"
                                    disabled={processing || code.length < 6}
                                >
                                    {t(
                                        'ui.settings.security.two_factor.modal.confirm',
                                    )}
                                </Button>
                            </div>
                        </div>
                    {/snippet}
                </Form>
            {/if}
        </div>
    </DialogContent>
</Dialog>
