# Pest Browser Testing & Page Smoke Tests

- **New Page Smoke Test Requirement**: Whenever creating or adding a new frontend page or route, always add a corresponding Pest Browser Smoke Test in `tests/Browser/`.
- **Smoke Testing with `assertNoSmoke()`**: Every page test must visit the route and call `->assertNoSmoke()`. This executes a real headless Playwright browser to ensure zero JavaScript errors, zero unhandled runtime exceptions, and zero unexpected console logs.
