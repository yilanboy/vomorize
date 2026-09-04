<script lang="ts">
    import Github from '@/components/icons/Github.svelte';
    import { t } from '@/lib/i18n';
    import { cn } from '@/lib/utils';

    let { class: className = '' }: { class?: string } = $props();

    const currentYear = new Date().getFullYear();
</script>

<footer
    class={cn(
        'border-t border-border bg-background transition-colors duration-300',
        className,
    )}
>
    <div
        class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-6 text-sm text-muted-foreground sm:flex-row sm:px-6 lg:px-8"
    >
        <div class="text-center sm:text-left">
            <span>{t('ui.footer.copyright', { year: currentYear })}</span>
        </div>

        <div class="flex items-center gap-4">
            <a
                href="https://github.com/yilanboy/vomorize"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 text-muted-foreground transition-colors duration-200 hover:text-foreground"
                aria-label={t('ui.nav.github')}
            >
                <Github class="size-4.5" />
                <span class="font-medium">{t('ui.footer.github')}</span>
            </a>
        </div>
    </div>
</footer>
