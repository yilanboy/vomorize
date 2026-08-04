# Vomorize

Vomorize is a focused English vocabulary learning app built around spaced repetition. It helps learners work through a structured curriculum of approximately 7,000 words without requiring an account before they begin.

## Features

- Guest-first learning with progress stored locally in the browser.
- Seven levels of 1,000 words, divided into 100 groups of 10 words each.
- Six review stages with intervals of 12 hours, 1 day, 2 days, 4 days, 7 days, and 15 days.
- Twenty-question review sessions: each word is tested twice.
- Immediate answer feedback, example sentences, and on-demand audio.
- Custom quizzes for vocabulary from completed sessions.
- Traditional Chinese (`zh_TW`), Simplified Chinese (`zh_CN`), and Japanese (`ja`).
- Optional email/password and GitHub authentication.
- Automatic migration of guest progress when a learner creates an account or signs in.

## Technology

- **Backend:** Laravel 13 and PHP 8.5
- **Frontend:** Inertia.js 3, Svelte 5, and Tailwind CSS 4
- **Build tooling:** Vite and PNPM
- **Database:** SQLite for local development; PostgreSQL for production
- **Testing:** Pest, PHPUnit, and Playwright browser tests
- **Code quality:** Laravel Pint, PHPStan, Oxlint, Oxfmt, and Svelte Check

## Requirements

- PHP 8.5+
- Composer
- Node.js 26+
- PNPM 11+
- SQLite for local development

## Installation

Clone the repository and enter the project directory:

```bash
git clone <repository-url>
cd vomorize
```

Install PHP dependencies, create the local environment, migrate the database, install frontend dependencies, and build the assets:

```bash
composer setup
```

Load the vocabulary and development data:

```bash
php artisan db:seed
```

If you prefer to run the setup steps individually:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
pnpm install
pnpm run build
```

The default local configuration uses SQLite. Copy `.env.example` to `.env` and update the database or authentication settings when using another environment.

## Development

Start the Laravel and Vite development processes with:

```bash
composer run dev
```

The application is available at [http://localhost:8000](http://localhost:8000) by default.

To run only the frontend development server:

```bash
pnpm run dev
```

## Testing and quality checks

Run the full Laravel test suite:

```bash
php artisan test --compact
```

Run the complete project checks:

```bash
composer run ci:check
```

Individual checks are also available:

```bash
vendor/bin/pint --dirty --format agent
pnpm run lint:check
pnpm run format:check
pnpm run types:check
```

Browser tests use the Pest browser plugin and Playwright. Ensure the application and required browser dependencies are available before running the browser test suite.

## Learning model

A score of 60% or higher passes a session and advances the group to the next review stage. A failed first attempt has a 12-hour retry delay; failures from later stages have a 1-day delay. Completed groups can be reviewed again without a cooldown.

Guest progress is stored in browser `localStorage`. Authenticated progress is stored by Laravel, allowing it to follow the learner across devices. When progress is migrated, the more advanced progress for each group is retained.

## Project structure

```text
app/                 Laravel application code
database/            Migrations, factories, seeders, and SQLite database
resources/js/pages/  Inertia/Svelte pages
resources/js/        Shared frontend components and utilities
routes/              Web and settings routes
tests/               Feature, unit, and browser tests
```

## License

This project is distributed under the MIT license.
