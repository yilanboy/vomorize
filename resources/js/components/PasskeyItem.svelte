<script lang="ts">
    import Fingerprint from '@lucide/svelte/icons/fingerprint';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Spinner } from '@/components/ui/spinner';
    import { t } from '@/lib/i18n';
    import type { Passkey } from '@/types/auth';

    let {
        passkey,
        onDelete,
    }: {
        passkey: Passkey;
        onDelete?: (id: number, onError: () => void) => void;
    } = $props();

    let isDeleting = $state(false);

    const handleDelete = () => {
        isDeleting = true;
        onDelete?.(passkey.id, () => {
            isDeleting = false;
        });
    };
</script>

<div
    class="hover:bg-muted/30 flex items-center justify-between p-4 transition-colors"
>
    <div class="flex items-center gap-3.5">
        <div
            class="bg-muted text-muted-foreground flex size-10 shrink-0 items-center justify-center rounded-xl"
        >
            <Fingerprint class="size-5" />
        </div>
        <div class="space-y-1">
            <div class="flex items-center gap-2.5">
                <p class="text-foreground text-sm font-semibold tracking-tight">
                    {passkey.name}
                </p>
                {#if passkey.authenticator}
                    <span
                        class="bg-muted text-muted-foreground ring-border/80 inline-flex items-center gap-1 rounded-md px-2.5 py-0.5 text-sm font-medium tracking-wide uppercase ring-1 ring-inset"
                    >
                        {passkey.authenticator}
                    </span>
                {/if}
            </div>
            <p class="text-muted-foreground text-sm">
                {t('ui.settings.security.passkeys.item.added', {
                    time: passkey.created_at_diff,
                })}
                {#if passkey.last_used_at_diff}
                    <span class="text-muted-foreground/50 mx-1">·</span>
                    {t('ui.settings.security.passkeys.item.last_used', {
                        time: passkey.last_used_at_diff,
                    })}
                {/if}
            </p>
        </div>
    </div>

    <Dialog>
        <DialogTrigger>
            {#snippet child({ props })}
                <Button
                    variant="ghost"
                    size="icon"
                    class="text-muted-foreground hover:text-destructive hover:bg-destructive/10 size-9 rounded-lg transition-colors"
                    {...props}
                >
                    <Trash2 class="size-4" />
                    <span class="sr-only">
                        {t('ui.settings.security.passkeys.item.remove_sr')}
                    </span>
                </Button>
            {/snippet}
        </DialogTrigger>

        <DialogContent class="rounded-2xl p-6 sm:max-w-md sm:p-7">
            <div class="space-y-2">
                <DialogTitle
                    class="text-foreground text-xl font-bold tracking-tight"
                >
                    {t('ui.settings.security.passkeys.delete_dialog.title')}
                </DialogTitle>
                <DialogDescription
                    class="text-muted-foreground text-sm leading-relaxed"
                >
                    {t(
                        'ui.settings.security.passkeys.delete_dialog.description',
                        {
                            name: passkey.name,
                        },
                    )}
                </DialogDescription>
            </div>
            <DialogFooter
                class="flex flex-col-reverse gap-2 pt-3 sm:flex-row sm:justify-end"
            >
                <DialogClose>
                    {#snippet child({ props })}
                        <Button
                            variant="outline"
                            class="h-11 w-full rounded-xl text-sm font-medium sm:w-auto"
                            {...props}
                        >
                            {t(
                                'ui.settings.security.passkeys.delete_dialog.cancel',
                            )}
                        </Button>
                    {/snippet}
                </DialogClose>
                <Button
                    variant="destructive"
                    class="h-11 w-full rounded-xl text-sm font-semibold sm:w-auto"
                    disabled={isDeleting}
                    onclick={handleDelete}
                >
                    {#if isDeleting}
                        <Spinner class="size-4" />
                        <span
                            >{t(
                                'ui.settings.security.passkeys.delete_dialog.loading',
                            )}</span
                        >
                    {:else}
                        <span
                            >{t(
                                'ui.settings.security.passkeys.delete_dialog.confirm',
                            )}</span
                        >
                    {/if}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</div>
