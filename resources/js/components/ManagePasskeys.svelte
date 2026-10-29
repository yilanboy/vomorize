<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Fingerprint from '@lucide/svelte/icons/fingerprint';
    import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
    import PasskeyItem from '@/components/PasskeyItem.svelte';
    import PasskeyRegister from '@/components/PasskeyRegister.svelte';
    import { t } from '@/lib/i18n';
    import type { Passkey } from '@/types/auth';

    export type Props = {
        canManagePasskeys?: boolean;
        passkeys?: Passkey[];
    };

    let { canManagePasskeys = false, passkeys = [] }: Props = $props();

    const handleDelete = (id: number, onError: () => void) => {
        router.delete(destroy.url(id), {
            preserveScroll: true,
            onError,
        });
    };

    const handleRegisterSuccess = () => {
        router.reload();
    };
</script>

{#if canManagePasskeys}
    <div
        class="border-border/80 bg-card space-y-6 rounded-2xl border p-6 shadow-xs sm:p-7"
    >
        <header class="flex items-start gap-3">
            <div
                class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl"
            >
                <Fingerprint class="size-5" />
            </div>
            <div>
                <h2
                    class="text-foreground text-lg font-semibold tracking-tight"
                >
                    {t('ui.settings.security.passkeys.title')}
                </h2>
                <p class="text-muted-foreground mt-0.5 text-sm">
                    {t('ui.settings.security.passkeys.subtitle')}
                </p>
            </div>
        </header>

        <div
            class="border-border/80 divide-border/80 bg-background divide-y overflow-hidden rounded-xl border"
        >
            {#if passkeys.length > 0}
                {#each passkeys as passkey (passkey.id)}
                    <PasskeyItem {passkey} onDelete={handleDelete} />
                {/each}
            {:else}
                <div class="p-8 text-center">
                    <div
                        class="bg-primary/10 text-primary mx-auto mb-3 flex size-12 items-center justify-center rounded-2xl"
                    >
                        <Fingerprint class="size-6" />
                    </div>
                    <p class="text-foreground text-base font-semibold">
                        {t('ui.settings.security.passkeys.empty_title')}
                    </p>
                    <p
                        class="text-muted-foreground mx-auto mt-1 max-w-sm text-sm leading-relaxed"
                    >
                        {t('ui.settings.security.passkeys.empty_desc')}
                    </p>
                </div>
            {/if}
        </div>

        <PasskeyRegister onSuccess={handleRegisterSuccess} />
    </div>
{/if}
