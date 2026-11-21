---
paths:
  - tests/**
  - phpunit.xml
  - database/factories/**
---

# Tests

## Run tests against a separate testing database on the production engine

Tests run against a dedicated testing database (e.g. `tayari_testing`) on the same engine as production: MySQL now, PostgreSQL after the move. Never SQLite and never the development database. SQLite can't stand in for the production engine here: the schema uses JSON columns (spatie/laravel-translatable names) and may use spatial columns, which SQLite doesn't support or handles differently.

Before running anything, check `phpunit.xml`'s `<php>` block:

- `DB_CONNECTION` must match the production engine (`mysql` now, `pgsql` after the move) and `DB_DATABASE` must name the testing database. If it still says `sqlite` / `:memory:` (the Laravel skeleton default), tests are not running against the production engine: stop and report it rather than trusting the results.
- If a `.env.testing` exists, check its `DB_*` values too; `phpunit.xml` `<env>` values override it unless they use `force="false"`.

Never edit `phpunit.xml` to change the database. Override `DB_*` on the command line if needed (e.g. `DB_CONNECTION=mysql DB_DATABASE=tayari_testing php artisan test ...`). Tests that use `RefreshDatabase` or `DatabaseTruncation` wipe the target database, so only run against a database you know is disposable. If you don't know which database is safe, ask before running anything.

## Run only the related tests

Never run the full suite unless asked. Run the test files you changed plus the tests that cover the changed code (`grep -rl '<ChangedClass>' tests/`), using explicit paths or `--filter`. If a test fails, check it against the base branch before blaming your change.

## PHPUnit only

Write PHPUnit test classes (`php artisan make:test <Name>`, `--unit` for unit tests). Don't write Pest tests. Most tests should be feature tests that hit the HTTP layer (`getJson`, `postJson`), acting as the right side (client, Tayar or admin). Money and order-status tests assert the `wallet_transactions` and `order_status_histories` rows, not only the response.

## Use model factories and their states

Build test data with the model factories in `database/factories/`, and check for an existing state before setting attributes by hand. Follow the surrounding test's choice of `$this->faker` or `fake()`.

## Never delete tests without approval

Don't remove or skip existing tests or test files without explicit approval, even if they fail. Report the failure instead.
