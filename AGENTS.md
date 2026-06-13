# Repository Guidelines

## Project Structure & Module Organization

This is a Laravel 12 application. Core PHP code lives in `app/`: controllers and middleware in `app/Http`, models in `app/Models`, exports in `app/Exports`, and shared domain logic in `app/Services`. Web routes are in `routes/web.php`; console routes are in `routes/console.php`. Blade templates are in `resources/views`, frontend entry points in `resources/css` and `resources/js`, and public assets in `public/`. Database work belongs in `database/migrations`, `database/factories`, and `database/seeders`. Tests are split between `tests/Feature` and `tests/Unit`.

## Build, Test, and Development Commands

- `composer install` installs PHP dependencies.
- `npm install` installs Vite, Tailwind, Flowbite, and frontend tooling.
- `composer run setup` installs dependencies, creates `.env`, generates the app key, runs migrations, and builds assets.
- `composer run dev` starts Laravel, the queue listener, log tailing, and Vite concurrently for local development.
- `npm run dev` starts only the Vite dev server.
- `npm run build` builds production frontend assets.
- `composer test` clears config and runs the Laravel test suite.

## Data Import Commands

Bangorejo Excel import helpers live in `app/Console/Commands`. Run them with `--dry-run` first to validate rows and review automatic corrections before writing to the database.

- `php artisan import:ppwp-folder --dry-run`
- `php artisan import:dpd-folder --dry-run`
- `php artisan import:dpr-ri-folder --dry-run`
- `php artisan import:bangorejo-dprd-prov --dry-run`
- `php artisan import:bangorejo-dprd-kab --dry-run`

`import:ppwp-folder`, `import:dpd-folder`, and `import:dpr-ri-folder` are temporary historical-data helpers for folders of Excel files where each file is one kecamatan and each sheet is one desa. Each command accepts an optional path argument, for example `php artisan import:ppwp-folder "storage/import/PPWP" --dry-run`, `php artisan import:dpd-folder "storage/import/DPD" --dry-run`, or `php artisan import:dpr-ri-folder "storage/import/DPR RI" --dry-run`.

## Coding Style & Naming Conventions

Follow `.editorconfig`: UTF-8, LF endings, final newline, trimmed trailing whitespace, 4-space indentation, and 2-space YAML indentation. PHP should follow Laravel conventions and PSR-4 namespaces (`App\...` maps to `app/`). Use StudlyCase for classes such as `RoleHierarchyAccessTest`, camelCase for methods and variables, snake_case for migrations and database columns, and existing Blade filename patterns. Format PHP with Laravel Pint before submitting: `vendor/bin/pint`.

## Testing Guidelines

PHPUnit is configured in `phpunit.xml`. Feature tests go in `tests/Feature`; isolated service or helper tests go in `tests/Unit`. Name test classes after the behavior under test, ending in `Test`, for example `RoleHierarchyAccessTest`. Tests use in-memory SQLite, array cache/session drivers, sync queues, and array mail, so avoid relying on local `.env` state. Run `composer test` before opening a pull request.

## Commit & Pull Request Guidelines

Recent commits use short imperative subjects such as `Add role komisioner` and `Clean up code`. Keep commits concise, scoped to one change, and present tense. Pull requests should include a summary, testing notes, linked issues when available, and screenshots for UI changes in `resources/views`, `resources/css`, or `resources/js`.

## Security & Configuration Tips

Do not commit secrets or machine-specific `.env` values. Keep configuration changes in `config/` and document required environment variables in `.env.example`. Use migrations and seeders for schema or reference-data changes instead of editing local SQLite data directly.
