<script lang="ts">
    import AlertTriangle from '@lucide/svelte/icons/alert-triangle';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { t } from '@/lib/i18n';

    let {
        open = $bindable(false),
        title = t('ui.intro.top_bar.leave_dialog_title'),
        description = t('ui.intro.top_bar.leave_dialog_desc'),
        cancelText = t('ui.intro.top_bar.leave_dialog_cancel'),
        confirmText = t('ui.intro.top_bar.leave_dialog_confirm'),
        onConfirm,
        onCancel,
    }: {
        open?: boolean;
        title?: string;
        description?: string;
        cancelText?: string;
        confirmText?: string;
        onConfirm: () => void;
        onCancel?: () => void;
    } = $props();

    function handleCancel() {
        open = false;
        onCancel?.();
    }

    function handleConfirm() {
        open = false;
        onConfirm();
    }
</script>

<Dialog bind:open>
    <DialogContent class="sm:max-w-md">
        <div class="flex flex-col items-center justify-center text-center">
            <div
                class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
            >
                <AlertTriangle class="size-6" />
            </div>
            <DialogTitle class="text-lg font-bold">
                {title}
            </DialogTitle>
            <DialogDescription
                class="text-muted-foreground mt-2 text-sm leading-relaxed"
            >
                {description}
            </DialogDescription>
        </div>

        <DialogFooter class="flex flex-col gap-2 pt-4 sm:flex-row">
            <button
                type="button"
                class="hover:bg-muted text-foreground border-border/80 flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                onclick={handleCancel}
            >
                {cancelText}
            </button>
            <button
                type="button"
                class="flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-sm font-semibold text-white transition-colors hover:bg-red-700"
                onclick={handleConfirm}
            >
                {confirmText}
            </button>
        </DialogFooter>
    </DialogContent>
</Dialog>
