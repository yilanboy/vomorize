<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import Monitor from '@lucide/svelte/icons/monitor';
    import Moon from '@lucide/svelte/icons/moon';
    import Sun from '@lucide/svelte/icons/sun';
    import type { Component } from 'svelte';
    import { t } from '@/lib/i18n';
    import { themeState } from '@/lib/theme.svelte';
    import type { Appearance } from '@/types';

    const { appearance, updateAppearance } = themeState();

    interface TabItem {
        value: Appearance;
        icon: Component<{ class?: string }>;
        labelKey: string;
    }

    const tabs: TabItem[] = [
        {
            value: 'light',
            icon: Sun,
            labelKey: 'ui.settings.appearance.tabs.light',
        },
        {
            value: 'dark',
            icon: Moon,
            labelKey: 'ui.settings.appearance.tabs.dark',
        },
        {
            value: 'system',
            icon: Monitor,
            labelKey: 'ui.settings.appearance.tabs.system',
        },
    ];

    function handleAppearanceChange(value: Appearance) {
        updateAppearance(value);
    }
</script>

<div
    class="grid grid-cols-1 gap-4 sm:grid-cols-3"
    role="radiogroup"
    aria-label={t('ui.settings.appearance.title')}
>
    {#each tabs as { value, icon: Icon, labelKey } (value)}
        {@const isSelected = appearance.value === value}
        <button
            type="button"
            role="radio"
            aria-checked={isSelected}
            onclick={() => handleAppearanceChange(value)}
            class={{
                'flex cursor-pointer flex-col items-stretch gap-3 rounded-2xl border p-4 text-left transition-all': true,
                'border-primary bg-primary/5 ring-primary/20 shadow-xs ring-2':
                    isSelected,
                'border-border/80 bg-card hover:border-border hover:bg-accent/40':
                    !isSelected,
            }}
        >
            <!-- Visual Theme Preview Miniature -->
            <div
                class="border-border/60 relative h-20 w-full overflow-hidden rounded-xl border transition-transform duration-200 group-hover:scale-[1.01]"
            >
                {#if value === 'light'}
                    <div
                        class="flex h-full w-full flex-col justify-between bg-slate-100 p-2.5"
                    >
                        <div class="flex items-center justify-between">
                            <div class="bg-primary h-2 w-12 rounded-full"></div>
                            <div class="size-2 rounded-full bg-slate-300"></div>
                        </div>
                        <div
                            class="space-y-1.5 rounded-lg border border-slate-200/80 bg-white p-2 shadow-2xs"
                        >
                            <div
                                class="h-1.5 w-16 rounded-full bg-slate-400"
                            ></div>
                            <div
                                class="h-1 w-24 rounded-full bg-slate-200"
                            ></div>
                        </div>
                    </div>
                {:else if value === 'dark'}
                    <div
                        class="flex h-full w-full flex-col justify-between bg-zinc-950 p-2.5"
                    >
                        <div class="flex items-center justify-between">
                            <div class="bg-primary h-2 w-12 rounded-full"></div>
                            <div class="size-2 rounded-full bg-zinc-700"></div>
                        </div>
                        <div
                            class="space-y-1.5 rounded-lg border border-zinc-800 bg-zinc-900 p-2 shadow-2xs"
                        >
                            <div
                                class="h-1.5 w-16 rounded-full bg-zinc-300"
                            ></div>
                            <div
                                class="h-1 w-24 rounded-full bg-zinc-700"
                            ></div>
                        </div>
                    </div>
                {:else}
                    <!-- Split Preview for System -->
                    <div class="flex h-full w-full">
                        <div
                            class="border-border/40 flex h-full w-1/2 flex-col justify-between border-r bg-slate-100 p-2.5"
                        >
                            <div class="bg-primary h-2 w-8 rounded-full"></div>
                            <div
                                class="space-y-1 rounded-l border border-slate-200/80 bg-white p-1.5"
                            >
                                <div
                                    class="h-1.5 w-8 rounded-full bg-slate-400"
                                ></div>
                            </div>
                        </div>
                        <div
                            class="flex h-full w-1/2 flex-col justify-between bg-zinc-950 p-2.5"
                        >
                            <div
                                class="ml-auto size-2 rounded-full bg-zinc-700"
                            ></div>
                            <div
                                class="space-y-1 rounded-r border border-zinc-800 bg-zinc-900 p-1.5"
                            >
                                <div
                                    class="h-1.5 w-8 rounded-full bg-zinc-300"
                                ></div>
                            </div>
                        </div>
                    </div>
                {/if}
            </div>

            <!-- Label and Checkbox/Radio Indicator -->
            <div class="flex items-center justify-between pt-1">
                <div class="flex items-center gap-2">
                    <Icon class="text-muted-foreground size-4" />
                    <span class="text-foreground text-sm font-semibold">
                        {t(labelKey)}
                    </span>
                </div>
                {#if isSelected}
                    <div
                        class="bg-primary text-primary-foreground flex size-5 items-center justify-center rounded-full"
                    >
                        <Check class="size-3" />
                    </div>
                {:else}
                    <div
                        class="border-border/80 size-5 rounded-full border"
                    ></div>
                {/if}
            </div>
        </button>
    {/each}
</div>
