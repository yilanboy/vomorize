# Git Branch Naming Convention

- **Format & Prefix**: Branch names must follow the Angular Conventional Commit category conventions:
  - `feat/<branch-name>`: New features or capabilities.
  - `fix/<branch-name>`: Bug fixes and issue patches.
  - `docs/<branch-name>`: Documentation, AI guidelines, or README updates.
  - `refactor/<branch-name>`: Code refactoring with no functional change.
  - `test/<branch-name>`: Adding or improving test suites.
  - `chore/<branch-name>`: Tooling, dependencies, or configuration maintenance.
- **Language & Casing**:
  - **Strictly English**: Branch names must only use English words. Strictly NO Chinese characters or localized glyphs.
  - **Kebab-Case**: Lowercase alphanumeric characters separated by hyphens (e.g. `feat/all-16-dashboard-overview`).
- **Linear Issue Integration**:
  - When working on a Linear issue, prefix the branch descriptor with the lowercase issue ID: `<type>/<issue-id>-<short-description>` (e.g., `feat/all-16-dashboard-overview`).
