<script lang="ts">
    import EarOff from '@lucide/svelte/icons/ear-off';
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
        onConfirm,
        onCancel,
    }: {
        open?: boolean;
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
                <EarOff class="size-6" />
            </div>
            <DialogTitle class="text-lg font-bold">
                {t('ui.quiz.disable_listening_dialog.title')}
            </DialogTitle>
            <DialogDescription
                class="text-muted-foreground mt-2 text-sm leading-relaxed"
            >
                {t('ui.quiz.disable_listening_dialog.desc')}
            </DialogDescription>
        </div>

        <DialogFooter class="flex flex-col gap-2 pt-4 sm:flex-row">
            <button
                type="button"
                class="hover:bg-muted text-foreground border-border/80 flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                onclick={handleCancel}
            >
                {t('ui.quiz.disable_listening_dialog.cancel')}
            </button>
            <button
                type="button"
                class="bg-primary hover:bg-primary/90 flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-xl text-sm font-semibold text-white transition-colors"
                onclick={handleConfirm}
            >
                {t('ui.quiz.disable_listening_dialog.confirm')}
            </button>
        </DialogFooter>
    </DialogContent>
</Dialog>
