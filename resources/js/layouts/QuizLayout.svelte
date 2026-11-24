<script module lang="ts">
    export interface QuizLayoutProps {
        currentStep?: number;
        totalSteps?: number;
        leaveTitle?: string;
        onLeave?: () => void;
        listening?: {
            disabled: boolean;
            onDisable: () => void;
        } | null;
        footer?: {
            canAdvance: boolean;
            label: string;
            onAdvance: () => void;
        } | null;
    }
</script>

<script lang="ts">
    import EarOff from '@lucide/svelte/icons/ear-off';
    import X from '@lucide/svelte/icons/x';
    import type { Snippet } from 'svelte';
    import DisableListeningDialog from '@/components/DisableListeningDialog.svelte';
    import QuizActionFooter from '@/components/quiz/QuizActionFooter.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import { t } from '@/lib/i18n';

    let {
        currentStep = 1,
        totalSteps = 0,
        leaveTitle = '',
        onLeave,
        listening = null,
        footer = null,
        children,
    }: QuizLayoutProps & {
        children?: Snippet;
    } = $props();

    let isDisableListeningDialogOpen = $state(false);

    const displayStep = $derived(
        totalSteps > 0 ? Math.min(currentStep, totalSteps) : 0,
    );
    const progressPercent = $derived(
        totalSteps > 0
            ? Math.min(100, Math.round((displayStep / totalSteps) * 100))
            : 0,
    );

    function handleConfirmDisableListening() {
        isDisableListeningDialogOpen = false;
        listening?.onDisable();
    }
</script>

<div
    class="bg-background text-foreground flex min-h-screen flex-col transition-colors duration-300 selection:bg-blue-600 selection:text-white"
>
    <!-- Focused Immersive Top Bar -->
    <header
        class="border-border/80 bg-background/80 sticky top-0 z-40 border-b backdrop-blur-md"
    >
        <div
            class="mx-auto flex h-16 max-w-4xl items-center justify-between px-4 sm:px-6"
        >
            <!-- Left: Leave button -->
            <button
                type="button"
                class="hover:bg-muted text-muted-foreground hover:text-foreground flex size-10 cursor-pointer items-center justify-center rounded-xl transition-colors"
                onclick={onLeave}
                title={leaveTitle || t('ui.intro.top_bar.leave')}
            >
                <X class="size-5" />
            </button>

            <!-- Center: Progress indicator -->
            <div class="flex flex-1 flex-col items-center gap-1.5 px-4 sm:px-8">
                <div class="flex items-center gap-2">
                    <span
                        data-test="current-step"
                        class="text-foreground font-mono text-sm font-semibold"
                    >
                        {displayStep}
                    </span>
                    <span class="text-muted-foreground text-sm">/</span>
                    <span class="text-muted-foreground font-mono text-sm">
                        {totalSteps}
                    </span>
                </div>

                <div
                    class="bg-muted h-2 w-full max-w-xs overflow-hidden rounded-full sm:max-w-sm"
                >
                    <div
                        class="bg-primary h-full rounded-full transition-all duration-300 ease-out"
                        style:width="{progressPercent}%"
                    ></div>
                </div>
            </div>

            <!-- Right: Disable listening toggle button -->
            {#if listening}
                <button
                    type="button"
                    data-test="disable-listening-button"
                    class={{
                        'flex size-10 items-center justify-center rounded-xl transition-colors': true,
                        'hover:bg-muted text-muted-foreground hover:text-foreground cursor-pointer':
                            !listening.disabled,
                        'text-muted-foreground/40 cursor-not-allowed':
                            listening.disabled,
                    }}
                    disabled={listening.disabled}
                    onclick={() => {
                        isDisableListeningDialogOpen = true;
                    }}
                    title={listening.disabled
                        ? t('ui.quiz.top_bar.listening_disabled')
                        : t('ui.quiz.top_bar.cant_listen')}
                >
                    <EarOff class="size-5" />
                </button>
            {:else}
                <div class="size-10" aria-hidden="true"></div>
            {/if}
        </div>
    </header>

    <!-- Main Content Area -->
    <main
        class="flex flex-1 flex-col items-center justify-center px-4 py-6 sm:px-6"
    >
        {@render children?.()}
    </main>

    {#if footer}
        <QuizActionFooter
            canAdvance={footer.canAdvance}
            label={footer.label}
            onAdvance={footer.onAdvance}
        />
    {/if}

    {#if listening}
        <DisableListeningDialog
            bind:open={isDisableListeningDialogOpen}
            onConfirm={handleConfirmDisableListening}
        />
    {/if}

    <Toaster />
</div>
