<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    class={className}
    {...rest}
>
    <!-- Back stacked flashcard (SRS deck layer) -->
    <path
        fill="currentColor"
        fill-opacity="0.35"
        d="M15.1 2.35a1.8 1.8 0 0 1 2.37-.94l2.76 1.18a1.8 1.8 0 0 1 .94 2.37l-.72 1.68-6.07-2.6.72-1.69Z"
    />
    <!-- Right flashcard wing (tucked behind front card with diagonal seam) -->
    <path
        fill="currentColor"
        d="M13.28 10.45 15.72 4.7a2 2 0 0 1 2.63-1.05l2.21.94a2 2 0 0 1 1.05 2.63l-5.32 12.48a2 2 0 0 1-.87.97l-2.14-5.02v-.01l-.01-.01-1.99-5.18Z"
    />
    <!-- Front flashcard wing (left-to-apex primary card) -->
    <path
        fill="currentColor"
        d="M3.39 7.22a2 2 0 0 1 1.05-2.63l2.21-.94a2 2 0 0 1 2.63 1.05l5.95 13.96a2.1 2.1 0 0 1-1.11 2.76l-2.06.88a2.1 2.1 0 0 1-2.76-1.11L3.39 7.22Z"
    />
</svg>
