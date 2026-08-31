<script lang="ts">
    import Eye from '@lucide/svelte/icons/eye';
    import EyeOff from '@lucide/svelte/icons/eye-off';
    import { Input } from '@/components/ui/input';
    import { cn } from '@/lib/utils';

    let { class: className = '', ...rest } = $props();

    let showPassword = $state(false);
</script>

<div class="relative">
    <Input
        type={showPassword ? 'text' : 'password'}
        class={cn('pr-10', className)}
        {...rest}
    />
    <button
        type="button"
        onclick={() => (showPassword = !showPassword)}
        class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-3 text-zinc-500 hover:text-zinc-900 focus-visible:ring-[3px] focus-visible:ring-zinc-400 focus-visible:outline-none dark:text-zinc-400 dark:hover:text-zinc-50 dark:focus-visible:ring-zinc-600"
        aria-label={showPassword ? 'Hide password' : 'Show password'}
        tabindex={-1}
    >
        {#if showPassword}
            <EyeOff class="size-4" />
        {:else}
            <Eye class="size-4" />
        {/if}
    </button>
</div>
