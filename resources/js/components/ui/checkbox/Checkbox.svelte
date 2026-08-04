<script lang="ts">
    import { cn } from '@/lib/utils';
    import Check from '@lucide/svelte/icons/check';

    let {
        checked = $bindable(false),
        disabled = false,
        class: className = '',
        id,
        name,
        value,
        ...rest
    }: {
        checked?: boolean;
        disabled?: boolean;
        class?: string;
        id?: string;
        name?: string;
        value?: string;
        [key: string]: unknown;
    } = $props();
</script>

<button
    type="button"
    role="checkbox"
    aria-checked={checked}
    data-state={checked ? 'checked' : 'unchecked'}
    data-slot="checkbox"
    {disabled}
    {id}
    class={cn(
        'peer size-4 shrink-0 rounded-sm border border-zinc-300 dark:border-zinc-700 shadow-xs transition-shadow outline-none focus-visible:border-zinc-400 dark:focus-visible:border-zinc-600 focus-visible:ring-[3px] focus-visible:ring-zinc-400/50 dark:focus-visible:ring-zinc-600/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-red-600 dark:aria-invalid:border-red-400 aria-invalid:ring-red-600/20 dark:aria-invalid:ring-red-400/40 data-[state=checked]:border-zinc-900 data-[state=checked]:bg-zinc-900 data-[state=checked]:text-white dark:data-[state=checked]:border-zinc-50 dark:data-[state=checked]:bg-zinc-50 dark:data-[state=checked]:text-zinc-950',
        className,
    )}
    onclick={() => {
        if (!disabled) checked = !checked;
    }}
    {...rest}
>
    {#if checked}
        <div
            data-slot="checkbox-indicator"
            class="grid place-content-center text-current transition-none"
        >
            <Check class="size-3.5" />
        </div>
    {/if}
</button>
{#if name}
    <input type="hidden" {name} value={value ?? 'true'} disabled={!checked} />
{/if}
