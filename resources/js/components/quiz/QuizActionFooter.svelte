<script lang="ts">
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import { t } from '@/lib/i18n';

    let {
        canAdvance,
        label,
        onAdvance,
    }: {
        canAdvance: boolean;
        label: string;
        onAdvance: () => void;
    } = $props();
</script>

<aside
    class="border-border/80 bg-background/95 sticky bottom-0 z-30 border-t px-4 py-3.5 backdrop-blur-md sm:px-6"
    aria-label={t('ui.quiz.footer.aria_label')}
>
    <div class="mx-auto flex w-full max-w-xl justify-center">
        <button
            type="button"
            data-test="continue-button"
            disabled={!canAdvance}
            class={{
                'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl px-6 text-base font-semibold transition-all duration-150': true,
                'cursor-pointer bg-blue-600 text-white shadow-xs hover:bg-blue-700 active:scale-[0.98] dark:bg-blue-600 dark:hover:bg-blue-500':
                    canAdvance,
                'bg-muted text-muted-foreground cursor-not-allowed':
                    !canAdvance,
            }}
            onclick={onAdvance}
        >
            <span>{label}</span>
            <ArrowRight class="size-4.5" />
        </button>
    </div>
</aside>
