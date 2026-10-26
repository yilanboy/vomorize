# Pest Browser Testing & Page Smoke Tests

- **New Page Smoke Test Requirement**: Whenever creating or adding a new frontend page or route, always add a corresponding Pest Browser Smoke Test in `tests/Browser/`.
- **Smoke Testing with `assertNoSmoke()`**: Every page test must visit the route and call `->assertNoSmoke()`. This executes a real headless Playwright browser to ensure zero JavaScript errors, zero unhandled runtime exceptions, and zero unexpected console logs.
- **Directory Structure & Categorization**: Organize browser tests by authentication state and domain under `tests/Browser/`:
    - `PublicPagesTest.php`: Public, unauthenticated routes (`/`, `/levels`, `/levels/{level}`, `/login`, `/register`, etc.).
    - `LearningPagesTest.php`: SRS and vocabulary learning flows (`/groups/{group}/intro`, `/groups/{group}`).
    - `AuthenticatedPagesTest.php`: Authenticated member routes (`/dashboard`, `/settings/*`) using `actingAs($user)`.
- **Factory & Data Isolation**: For dynamic pages requiring database entities (e.g., levels, groups, vocabularies), construct minimal factory fixtures to render the page accurately.
- **Full CI Check**: Browser tests run as part of the Pest test suite during `composer ci:check`.
