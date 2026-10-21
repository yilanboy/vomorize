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
