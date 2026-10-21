# PHPStan Static Analysis

- After modifying PHP files, always run PHPStan with adequate memory limit (e.g. `vendor/bin/phpstan analyse --memory-limit=512M`) to ensure static analysis passes with zero errors.
- Avoid defensive over-engineering, such as using nullsafe `?->` on the left of `??` (which triggers `nullsafe.neverNull`) or redundant type casting on already cast model properties.
