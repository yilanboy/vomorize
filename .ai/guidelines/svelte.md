# Svelte 5 Best Practices & Guidelines

## When NOT to use `$effect` (Official Guidance)

`$effect` should be treated as an **escape hatch** (for analytics, direct DOM manipulation, Web Audio, or integrating non-reactive third-party libraries) rather than a frequent everyday tool.

1. **Do NOT use `$effect` to synchronize or derive state**:
    - Never use an effect to calculate state from other state:
        ```svelte
        // ❌ Anti-pattern
        let count = $state(0);
        let doubled = $state();
        $effect(() => {
            doubled = count * 2;
        });
        ```
    - Always use `$derived` or `$derived.by`:
        ```svelte
        // ✅ Correct let count = $state(0); let doubled = $derived(count * 2);
        ```
    - **Do NOT use `$effect` to sync props to local `$state`** (`$effect(() => { state = prop; })`). Use `$state(prop)` for locally captured initial state, or `$derived` if it should dynamically track prop updates.

2. **Do NOT use `$effect` just to reassign derived values (Optimistic UI)**:
    - As of Svelte 5.25+, derived values can be directly overridden and reassigned without needing an effect and a separate `$state`.

3. **Do NOT use `$effect` to link two interrelated inputs/states**:
    - Avoid creating circular or paired `$effect` blocks to keep two values in sync (e.g. `spent` and `left`).
    - Instead, use `oninput` handlers or **Function Bindings** (`bind:value={() => left, updateLeft}`).

4. **Do NOT use `$effect` for component mount/unmount lifecycle tasks**:
    - For setup/teardown tasks that should run strictly once when entering the DOM (e.g., countdown intervals, non-reactive timers, browser event listeners), prefer `onMount` (or `onDestroy`).
    - This prevents accidental reactive dependency tracking where updating state inside the timer tears down and recreates the effect every second.

5. **Breaking reactive loops with `untrack`**:
    - If you genuinely must update `$state` inside an effect and risk an infinite loop or unwanted re-execution, wrap the non-triggering reads with `untrack(() => state)` from `svelte`.
    - Note: Values read asynchronously (after `await` or inside `setTimeout`/`setInterval`) are **not** tracked by `$effect`.

---

## Dynamic Class Attributes: Objects Over Ternaries

In Svelte components, **always use Svelte's native object syntax (`class={{ ... }}`)** for dynamic class names. **Strictly avoid ternary operators** (`{cond ? 'a' : 'b'}`) inside class attributes or string templates.

### 1. Native HTML Elements (`class={{ ... }}`)

Place static base utility classes as a key mapped to `true`, and map dynamic classes to their respective boolean conditions:

```svelte
<!-- ❌ Anti-pattern: String interpolation with ternary operators -->
<div
    class="flex items-center gap-2 {isError
        ? 'text-red-500 bg-red-50'
        : 'text-green-500 bg-green-50'}"
>
    ...
</div>

<!-- ✅ Correct: Declarative object syntax -->
<div
    class={{
        'flex items-center gap-2': true,
        'text-red-500 bg-red-50': isError,
        'text-green-500 bg-green-50': !isError,
    }}
>
    ...
</div>
```

### 2. Multi-Condition & Computed Class Names

For multiple mutually exclusive states, declare each branch clearly. Computed class names can use standard JavaScript bracket notation (`[computedKey]`):

```svelte
<!-- ✅ Multi-condition state -->
<button
    class={{
        'flex h-12 w-12 items-center justify-center rounded-xl transition-all': true,
        'border-primary bg-primary text-white shadow-lg': isPlaying,
        'border-primary/50 bg-primary/10 animate-pulse':
            !isPlaying && isPending,
        'border-border/80 bg-muted text-foreground': !isPlaying && !isPending,
    }}
>
    ...
</button>

<!-- ✅ Computed class name with bracket notation -->
<span
    class={{
        'size-2.5 rounded-full': true,
        [stage.colorClass]: stage.count > 0,
        'bg-muted-foreground/30': stage.count <= 0,
    }}
></span>
```

### 3. Custom Svelte Component Props (`cn(...)`)

Custom Svelte components (such as shadcn-svelte UI components like `<Button>`) accept `class?: string` rather than native element object bindings. When passing dynamic classes to a component prop, use the `cn(...)` utility with an object argument instead of ternary operators:

```svelte
<!-- ❌ Anti-pattern -->
<Button class="w-full {isActive ? 'bg-muted' : ''}" />

<!-- ✅ Correct: Pass object into cn() -->
<Button
    class={cn('w-full', {
        'bg-muted': isActive,
    })}
/>
```

---

## Inertia.js (v3) Svelte Page Component Lifecycle

1. **Page Components are Remounted on Every Navigation by Default**:
    - In `@inertiajs/svelte`, the adapter wraps the leaf page component inside a Svelte `{#key key}` block (where `key = args.preserveState ? key : Date.now()`).
    - Unless `preserveState: true` is explicitly provided, **navigating between URLs that share the exact same page component (e.g. `/groups/1` -> `/groups/2`) will destroy the previous component instance and remount a new one**.
    - Consequently:
        - `onMount` **will always execute on every page navigation**.
        - Top-level `<script>` code re-evaluates synchronously on every page navigation.

2. **Page-Level Initialization & Side Effects (Audio Preload, Timers, Analytics)**:
    - Prefer `onMount` over `$effect` for page entrance tasks (such as preloading audio, initiating timers, or logging page views).
    - If `$effect` is used for page entrance tasks, props updating right before the Inertia component swap can trigger `$effect` on the old instance before it unmounts and then again on the newly mounted instance, causing **duplicate execution**.

3. **Layout Props (`setLayoutProps`) Placement**:
    - Call `setLayoutProps` directly at the **top level of `<script>`**, NOT inside `onMount` or `$effect`.
    - Calling `setLayoutProps` synchronously in `<script>` allows the layout store (`storeState.shared`) to be populated before initial DOM rendering, avoiding layout shift (CLS) or breadcrumbs flashing.
