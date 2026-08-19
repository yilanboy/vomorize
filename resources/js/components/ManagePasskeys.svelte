<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import KeyRound from '@lucide/svelte/icons/key-round';
    import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
    import PasskeyItem from '@/components/PasskeyItem.svelte';
    import PasskeyRegister from '@/components/PasskeyRegister.svelte';
    import { translations } from '@/lib/locale.svelte';
    import type { Passkey } from '@/types/auth';

    export type Props = {
        canManagePasskeys?: boolean;
        passkeys?: Passkey[];
    };

    let { canManagePasskeys = false, passkeys = [] }: Props = $props();
    let t = $derived(translations());

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
        class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs sm:p-8 dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="mb-6 space-y-1">
            <h2 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-50">
                {t['passkeys']}
            </h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {t['passkeys_subtitle']}
            </p>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100/30 dark:border-zinc-800 dark:bg-zinc-800/30"
        >
            {#if passkeys.length > 0}
                {#each passkeys as passkey (passkey.id)}
                    <PasskeyItem {passkey} onDelete={handleDelete} />
                {/each}
            {:else}
                <div class="p-8 text-center">
                    <div
                        class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800"
                    >
                        <KeyRound class="h-6 w-6 text-zinc-500 dark:text-zinc-400" />
                    </div>
                    <p class="font-semibold text-zinc-900 dark:text-zinc-50">
                        {t['no_passkeys']}
                    </p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {t['no_passkeys_desc']}
                    </p>
                </div>
            {/if}
        </div>

        <div class="mt-6">
            <PasskeyRegister onSuccess={handleRegisterSuccess} />
        </div>
    </div>
{/if}
