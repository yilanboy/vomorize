<script lang="ts">
    import { usePasskeyRegister } from '@laravel/passkeys/svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { translations } from '@/lib/locale.svelte';

    let {
        onSuccess,
    }: {
        onSuccess?: () => void;
    } = $props();

    const getDefaultPasskeyName = () => {
        const ua = navigator.userAgent;

        const browser = [
            { pattern: /Edg|Edge/, name: 'Edge' },
            { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
            { pattern: /Firefox|FxiOS/, name: 'Firefox' },
            { pattern: /Chrome|CriOS/, name: 'Chrome' },
            { pattern: /Safari/, name: 'Safari' },
        ].find(({ pattern }) => pattern.test(ua))?.name;

        const os = [
            { pattern: /iPhone/, name: 'iPhone' },
            { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
            { pattern: /Android/, name: 'Android' },
            { pattern: /Mac/, name: 'Mac' },
            { pattern: /Windows/, name: 'Windows' },
        ].find(({ pattern }) => pattern.test(ua))?.name;

        return [browser, os].filter(Boolean).join(' on ') || '';
    };

    let name = $state(getDefaultPasskeyName());
    let showForm = $state(false);
    let t = $derived(translations());
    const passkeyRegister = usePasskeyRegister({
        onSuccess: () => {
            name = '';
            showForm = false;
            onSuccess?.();
        },
    });

    const handleSubmit = async (event: SubmitEvent) => {
        event.preventDefault();

        if (!name.trim()) {
            return;
        }

        await passkeyRegister.register(name.trim());
    };

    const handleCancel = () => {
        showForm = false;
        name = '';
    };
</script>

{#if !passkeyRegister.isSupported}
    <div class="text-sm text-zinc-500 dark:text-zinc-400">
        {t['passkey_not_supported']}
    </div>
{:else if !showForm}
    <Button variant="outline" onclick={() => (showForm = true)}>
        {t['add_passkey']}
    </Button>
{:else}
    <form
        onsubmit={handleSubmit}
        class="space-y-4 rounded-xl border border-zinc-200 bg-zinc-100/30 p-4 dark:border-zinc-800 dark:bg-zinc-800/30"
    >
        <div class="grid gap-2.5">
            <Label for="passkey-name">{t['passkey_name']}</Label>
            <Input
                id="passkey-name"
                type="text"
                bind:value={name}
                placeholder={t['passkey_name_placeholder']}
                autofocus
            />
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {t['passkey_name_help']}
            </p>
        </div>

        {#if passkeyRegister.error}
            <InputError message={passkeyRegister.error} />
        {/if}

        <div class="flex gap-2">
            <Button type="submit" disabled={passkeyRegister.isLoading || !name.trim()}>
                {passkeyRegister.isLoading ? t['registering_passkey'] : t['confirm_add_passkey']}
            </Button>
            <Button type="button" variant="ghost" onclick={handleCancel}>
                {t['cancel']}
            </Button>
        </div>
    </form>
{/if}
