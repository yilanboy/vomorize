<script lang="ts">
    import KeyRound from '@lucide/svelte/icons/key-round';
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
    import { translations } from '@/lib/locale.svelte';
    import type { Passkey } from '@/types/auth';

    let {
        passkey,
        onDelete,
    }: {
        passkey: Passkey;
        onDelete?: (id: number, onError: () => void) => void;
    } = $props();

    let isDeleting = $state(false);
    let t = $derived(translations());

    const handleDelete = () => {
        isDeleting = true;
        onDelete?.(passkey.id, () => {
            isDeleting = false;
        });
    };
</script>

<div class="flex items-center justify-between border-b p-4 last:border-b-0">
    <div class="flex items-center gap-4">
        <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800"
        >
            <KeyRound class="h-5 w-5 text-zinc-500 dark:text-zinc-400" />
        </div>
        <div class="space-y-1">
            <div class="flex items-center gap-2.5">
                <p class="font-medium tracking-tight">{passkey.name}</p>
                {#if passkey.authenticator}
                    <span
                        class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-0.5 text-[11px] font-medium tracking-wide text-zinc-500 uppercase ring-1 ring-zinc-200 ring-inset dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700"
                    >
                        {passkey.authenticator}
                    </span>
                {/if}
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {t['added_at'].replace(':time', passkey.created_at_diff)}
                {#if passkey.last_used_at_diff}
                    <span class="mx-1 text-zinc-500/50 dark:text-zinc-400/50">/</span>
                    {t['last_used_at'].replace(':time', passkey.last_used_at_diff)}
                {/if}
            </p>
        </div>
    </div>

    <Dialog>
        <DialogTrigger asChild>
            {#snippet children(props)}
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-red-600 hover:bg-red-600/10 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-400/10 dark:hover:text-red-300"
                    onclick={props.onClick}
                >
                    <Trash2 class="h-4 w-4" />
                    <span class="sr-only">{t['remove_passkey_button']}</span>
                </Button>
            {/snippet}
        </DialogTrigger>

        <DialogContent>
            <DialogTitle>{t['remove_passkey_title']}</DialogTitle>
            <DialogDescription>
                {t['remove_passkey_desc'].replace(':name', passkey.name)}
            </DialogDescription>
            <DialogFooter>
                <DialogClose asChild>
                    {#snippet children(props)}
                        <Button variant="secondary" onclick={props.onClick}>
                            {t['cancel']}
                        </Button>
                    {/snippet}
                </DialogClose>
                <Button variant="destructive" disabled={isDeleting} onclick={handleDelete}>
                    {isDeleting ? t['removing_passkey'] : t['remove_passkey_button']}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</div>
