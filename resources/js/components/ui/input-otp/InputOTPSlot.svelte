<script lang="ts">
    import { cn } from '@/lib/utils';
    import { getInputOTPContext } from './context';

    let {
        index,
        class: className = '',
    }: {
        index: number;
        class?: string;
    } = $props();

    const ctx = getInputOTPContext();

    const char = $derived(ctx.value()[index]);
    const isActive = $derived(ctx.isFocused() && ctx.activeIndex() === index);
    const hasFakeCaret = $derived(isActive && !char);
</script>

<div
    data-slot="input-otp-slot"
    data-active={isActive}
    class={cn(
        'relative flex h-9 w-9 items-center justify-center border-y border-r border-zinc-300 bg-white text-sm text-zinc-900 shadow-xs transition-all outline-none first:rounded-l-md first:border-l last:rounded-r-md dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-50 aria-invalid:border-red-600 dark:aria-invalid:border-red-400 data-[active=true]:z-10 data-[active=true]:border-zinc-400 dark:data-[active=true]:border-zinc-600 data-[active=true]:ring-[3px] data-[active=true]:ring-zinc-400/50 dark:data-[active=true]:ring-zinc-600/50 data-[active=true]:aria-invalid:border-red-600 dark:data-[active=true]:aria-invalid:border-red-400 data-[active=true]:aria-invalid:ring-red-600/20 dark:data-[active=true]:aria-invalid:ring-red-400/40',
        className,
    )}
>
    {char ?? ''}
    {#if hasFakeCaret}
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
            <div class="h-4 w-px animate-caret-blink bg-zinc-900 dark:bg-zinc-50 duration-1000"></div>
        </div>
    {/if}
</div>
