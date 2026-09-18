<script lang="ts">
    import { usePasskeyRegister } from '@laravel/passkeys/svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { t } from '@/lib/i18n';

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
    <div class="text-muted-foreground text-sm">
        {t('ui.settings.security.passkeys.not_supported')}
    </div>
{:else if !showForm}
    <Button variant="outline" onclick={() => (showForm = true)}>
        {t('ui.settings.security.passkeys.add_button')}
    </Button>
{:else}
    <form
        onsubmit={handleSubmit}
        class="border-border bg-muted/50 space-y-4 rounded-lg border p-4"
    >
        <div class="grid gap-2">
            <Label for="passkey-name">
                {t('ui.settings.security.passkeys.form.name')}
            </Label>
            <Input
                id="passkey-name"
                type="text"
                bind:value={name}
                placeholder={t(
                    'ui.settings.security.passkeys.form.name_placeholder',
                )}
                class="border-foreground/20 mt-1 block w-full"
                autofocus
            />
            <p class="text-muted-foreground text-sm">
                {t('ui.settings.security.passkeys.form.name_hint')}
            </p>
        </div>

        {#if passkeyRegister.error}
            <InputError message={passkeyRegister.error} />
        {/if}

        <div class="flex gap-2">
            <Button
                type="submit"
                disabled={passkeyRegister.isLoading || !name.trim()}
            >
                {passkeyRegister.isLoading
                    ? t('ui.settings.security.passkeys.form.loading')
                    : t('ui.settings.security.passkeys.form.submit')}
            </Button>
            <Button type="button" variant="ghost" onclick={handleCancel}>
                {t('ui.settings.security.passkeys.form.cancel')}
            </Button>
        </div>
    </form>
{/if}
