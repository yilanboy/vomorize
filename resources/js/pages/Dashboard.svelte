<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import PlaceholderPattern from '@/components/PlaceholderPattern.svelte';
</script>

<AppHead title="Dashboard" />

<div
    class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
>
    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div
            class="border-border relative aspect-video overflow-hidden rounded-xl border"
        >
            <PlaceholderPattern />
        </div>
        <div
            class="border-border relative aspect-video overflow-hidden rounded-xl border"
        >
            <PlaceholderPattern />
        </div>
        <div
            class="border-border relative aspect-video overflow-hidden rounded-xl border"
        >
            <PlaceholderPattern />
        </div>
    </div>
    <div class="border-border relative min-h-[400px] flex-1 rounded-xl border">
        <PlaceholderPattern />
    </div>
</div>
