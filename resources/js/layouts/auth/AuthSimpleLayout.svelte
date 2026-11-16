<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { home } from '@/routes';

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

<div
    class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 transition-colors duration-300 md:p-10"
>
    <div class="w-full max-w-sm">
        <div class="flex flex-col gap-8">
            <div class="flex flex-col items-center gap-4">
                <Link
                    href={home()}
                    class="flex flex-col items-center gap-2 font-medium"
                >
                    <img
                        src="/favicon.svg"
                        alt=""
                        aria-hidden="true"
                        class="mb-1 size-10 rounded-xl shadow-xs"
                    />
                    <span
                        class="text-foreground text-xl font-bold tracking-tight"
                        >Vomorize</span
                    >
                    <span class="sr-only">{title}</span>
                </Link>
                <div class="space-y-2 text-center">
                    <h1 class="text-xl font-medium">{title}</h1>
                    <p class="text-muted-foreground text-center text-sm">
                        {description}
                    </p>
                </div>
            </div>
            {@render children?.()}
        </div>
    </div>
</div>
