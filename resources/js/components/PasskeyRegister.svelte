<script lang="ts">
    import { usePasskeyRegister } from '@laravel/passkeys/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
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
    <Button
        variant="outline"
        onclick={() => (showForm = true)}
        class="h-11 gap-2 rounded-xl px-4 text-sm font-semibold shadow-xs transition-all"
    >
        <Plus class="size-4" />
        <span>{t('ui.settings.security.passkeys.add_button')}</span>
    </Button>
{:else}
    <form
        onsubmit={handleSubmit}
        class="border-border/80 bg-muted/20 space-y-4 rounded-xl border p-5"
    >
        <div class="grid gap-1.5">
            <Label
                for="passkey-name"
                class="text-foreground text-sm font-semibold"
            >
                {t('ui.settings.security.passkeys.form.name')}
            </Label>
            <Input
                id="passkey-name"
                type="text"
                bind:value={name}
                placeholder={t(
                    'ui.settings.security.passkeys.form.name_placeholder',
                )}
                class="h-12 w-full rounded-xl text-sm"
                autofocus
            />
            <p class="text-muted-foreground text-sm leading-relaxed">
                {t('ui.settings.security.passkeys.form.name_hint')}
            </p>
        </div>

        {#if passkeyRegister.error}
            <InputError message={passkeyRegister.error} />
        {/if}

        <div class="flex gap-2.5 pt-1">
            <Button
                type="submit"
                class="h-11 gap-2 rounded-xl px-5 text-sm font-semibold shadow-xs transition-all"
                disabled={passkeyRegister.isLoading || !name.trim()}
            >
                {#if passkeyRegister.isLoading}
                    <Spinner class="size-4" />
                {/if}
                <span>
                    {passkeyRegister.isLoading
                        ? t('ui.settings.security.passkeys.form.loading')
                        : t('ui.settings.security.passkeys.form.submit')}
                </span>
            </Button>
            <Button
                type="button"
                variant="outline"
                class="h-11 rounded-xl px-4 text-sm font-medium"
                onclick={handleCancel}
            >
                {t('ui.settings.security.passkeys.form.cancel')}
            </Button>
        </div>
    </form>
{/if}
