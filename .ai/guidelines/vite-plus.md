# Frontend Verification (Vite Plus)

- **Code Formatting & Linting**: Always run `vp check` (or `vp check --fix` to automatically fix formatting) on modified files (including Svelte, TypeScript, Markdown, and YAML configuration files) to ensure zero formatting issues and zero lint errors.
- **Type Checking**: Run `pnpm run types:check` (`svelte-check --tsconfig ./tsconfig.json`) to verify zero type diagnostics or Svelte warnings.
- **Build Verification**: Run `pnpm run build` (or `vp build`) to ensure frontend assets compile successfully with zero errors and zero Svelte compiler warnings (such as `state_referenced_locally`).
- **Full CI Check**: Before pushing or merging, run `composer ci:check` to execute the complete verification suite (`vp check`, `svelte-check`, `phpstan`, `pint`, and `pest`).
