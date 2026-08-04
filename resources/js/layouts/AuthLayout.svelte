<script lang="ts">
    import type { Snippet } from 'svelte';
    import AuthCardLayout from '@/layouts/auth/AuthCardLayout.svelte';

    let {
        title = '',
        description = '',
        children,
    }: {
        title?: string;
        description?: string;
        children?: Snippet;
    } = $props();
</script>

<AuthCardLayout {title} {description}>
    {@render children?.()}
</AuthCardLayout>
