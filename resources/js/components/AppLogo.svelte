<script lang="ts">
    import { page } from '@inertiajs/svelte';

    const name = $derived(page.props.name);
</script>

<img
    src="/favicon.svg"
    alt=""
    aria-hidden="true"
    class="aspect-square size-8 rounded-md shadow-xs"
/>
<div class="ml-1 grid flex-1 text-left text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">{name}</span>
</div>
