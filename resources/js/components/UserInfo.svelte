<script lang="ts">
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { getFirstCharacter } from '@/lib/initials';
    import type { User } from '@/types';

    let {
        user,
        showEmail = false,
    }: {
        user: User;
        showEmail?: boolean;
    } = $props();
</script>

<Avatar class="h-8 w-8 overflow-hidden rounded-full">
    <AvatarFallback
        class="rounded-full bg-zinc-200 font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50"
    >
        {getFirstCharacter(user.name)}
    </AvatarFallback>
</Avatar>

<div class="grid flex-1 text-left text-sm leading-tight">
    <span class="truncate font-medium">{user.name}</span>
    {#if showEmail}
        <span class="truncate text-sm text-zinc-500 dark:text-zinc-400"
            >{user.email}</span
        >
    {/if}
</div>
