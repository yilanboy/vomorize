<script lang="ts">
    import type { UrlMethodPair } from '@inertiajs/core';
    import { router } from '@inertiajs/svelte';
    import { usePasskeyVerify } from '@laravel/passkeys/svelte';
    import Fingerprint from '@lucide/svelte/icons/fingerprint';
    import { untrack } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Separator } from '@/components/ui/separator';
    import { Spinner } from '@/components/ui/spinner';
    import { cn } from '@/lib/utils';

    type Props = {
        routes?: {
            options: UrlMethodPair;
            submit: UrlMethodPair;
        };
        label?: string;
        loadingLabel?: string;
        separator?: string;
        showSeparator?: boolean;
        class?: string;
    };

    let props: Props = $props();
    const initialRoutes = untrack(() => props.routes);

    const passkeyVerify = usePasskeyVerify({
        ...(initialRoutes
            ? {
                  routes: {
                      options: initialRoutes.options.url,
                      submit: initialRoutes.submit.url,
                  },
              }
            : {}),
        onSuccess: (response) => {
            const redirect = response.redirect;
            router.visit(redirect ?? '/dashboard');
        },
    });
</script>

{#if passkeyVerify.isSupported}
    <div class="grid gap-2">
        <Button
            type="button"
            variant="outline"
            class={cn('w-full', props.class)}
            onclick={passkeyVerify.verify}
            disabled={passkeyVerify.isLoading}
        >
            {#if passkeyVerify.isLoading}
                <Spinner />
            {:else}
                <Fingerprint class="size-4" />
            {/if}
            <span>
                {passkeyVerify.isLoading
                    ? (props.loadingLabel ?? 'Authenticating...')
                    : (props.label ?? 'Sign in with a passkey')}
            </span>
        </Button>

        {#if passkeyVerify.error}
            <div class="text-center">
                <InputError message={passkeyVerify.error} />
            </div>
        {/if}
    </div>

    {#if props.showSeparator ?? true}
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <Separator class="w-full" />
            </div>
            <div
                class="relative flex justify-center text-sm font-medium uppercase"
            >
                <span
                    class="bg-card text-muted-foreground px-3 text-xs font-medium lowercase first-letter:uppercase"
                >
                    {props.separator ?? 'Or continue with email'}
                </span>
            </div>
        </div>
    {/if}
{/if}
