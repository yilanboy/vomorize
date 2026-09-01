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

<div class="flex items-center justify-between border-b p-4 last:border-b-0">
    <div class="flex items-center gap-4">
        <div
            class="bg-muted flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
        >
            <KeyRound class="text-muted-foreground h-5 w-5" />
        </div>
        <div class="space-y-1">
            <div class="flex items-center gap-2.5">
                <p class="font-medium tracking-tight">{passkey.name}</p>
                {#if passkey.authenticator}
                    <span
                        class="bg-muted text-muted-foreground ring-border inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium tracking-wide uppercase ring-1 ring-inset"
                    >
                        {passkey.authenticator}
                    </span>
                {/if}
            </div>
            <p class="text-muted-foreground text-sm">
                Added {passkey.created_at_diff}
                {#if passkey.last_used_at_diff}
                    <span class="text-muted-foreground/50 mx-1">/</span>
                    Last used {passkey.last_used_at_diff}
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
                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                    onclick={props.onClick}
                >
                    <Trash2 class="h-4 w-4" />
                    <span class="sr-only">Remove</span>
                </Button>
            {/snippet}
        </DialogTrigger>

        <DialogContent>
            <DialogTitle>Remove passkey</DialogTitle>
            <DialogDescription>
                Are you sure you want to remove the "{passkey.name}" passkey?
                You will no longer be able to use it to sign in.
            </DialogDescription>
            <DialogFooter>
                <DialogClose asChild>
                    {#snippet children(props)}
                        <Button variant="secondary" onclick={props.onClick}>
                            Cancel
                        </Button>
                    {/snippet}
                </DialogClose>
                <Button
                    variant="destructive"
                    disabled={isDeleting}
                    onclick={handleDelete}
                >
                    {isDeleting ? 'Removing...' : 'Remove passkey'}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</div>
