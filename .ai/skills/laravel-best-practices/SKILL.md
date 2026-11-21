---
name: laravel-best-practices
description: "Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns."
license: MIT
metadata:
  author: laravel
---

# Laravel Best Practices

Best practices for Laravel, organized as an index of rule files. Each rule file teaches what to do and why. For exact API syntax, check the installed framework in `vendor/laravel/framework` or the Laravel 13.x documentation.

## Consistency First

Before applying any rule, check what the application already does. Laravel offers multiple valid approaches, and the best choice is the one the codebase already uses, even if another pattern would be theoretically better. Inconsistency is worse than a suboptimal pattern.

Check sibling files, related controllers, models, or tests for established patterns. If one exists, follow it. Don't introduce a second way. These rules are defaults for when no pattern exists yet, not overrides. When you edit existing code, matching its sibling files wins over the style rules in this skill.

The project rules in `.ai/rules/` take precedence over this skill. Read `.ai/rules/index.md` first.

## Tayari Context

Tayari is a same-day delivery app for Egypt. One Laravel backend serves one API to two clients: a React website and a Flutter mobile app. The mobile app has a client side (customers and businesses send orders) and a Tayar side (riders deliver them); these may later split into two apps, but the backend and database stay one. Check `composer.json`, `composer.lock` and the real files before relying on any of this:

- **PHP 8.3 or newer, Laravel 13** on the new skeleton: middleware, exceptions and routing are configured in `bootstrap/app.php`, scheduled tasks live in `routes/console.php`, and there is no `app/Http/Kernel.php` or `app/Console/Kernel.php`. The production database engine is MySQL 8 now on the shared host and PostgreSQL + PostGIS after the move to a VPS. Every environment uses the same engine as production, and queries use portable SQL only (see Portable SQL in `.ai/rules/architecture.md`).
- **Auth:** Laravel Sanctum, with stateful SPA cookies for React (`statefulApi()` in `bootstrap/app.php`) and Bearer tokens for Flutter. Admin roles and permissions use spatie/laravel-permission.
- **One `users` table** for everyone; `business_profiles` and `tayar_profiles` hold the extra per-role data.
- **API:** `routes/api/v1/{public,auth,client,tayar,admin}.php`, grouped per client (`client/`, `tayar/`, `admin/`). See `rules/routing.md`.
- **Files** go through spatie/laravel-medialibrary collections. **Lookup names** (Arabic/English) use spatie/laravel-translatable JSON columns.
- **Structure:** domain folders under `app/Domains/*` with one Action per business operation (`.ai/rules/architecture.md`). Business rules (money, order statuses, codes) are in `.ai/rules/domain-rules.md`.
- **Validation** always lives in FormRequests (`.ai/rules/validation.md`). Tests use PHPUnit (`.ai/rules/tests.md`). The formatter is Pint, and there is no static analysis.

## How to Apply

1. Check the changed files, nearby code, project configuration, and relevant tests for established patterns. Deviate only for a correctness or security defect, and call the deviation out.
2. Map every affected concern to the rule index below. Read each mapped rule file before editing. Skip unrelated rule files.
3. Make the smallest coherent change. Keep the application's architecture and naming instead of introducing a second pattern for the same job.
4. Verify version-sensitive Laravel APIs against the installed framework (`vendor/laravel/framework`, Laravel 13) before using them.
5. Run only the tests related to the change (see `.ai/rules/tests.md`), then `./vendor/bin/pint --test <changed files>`.
6. Re-read the diff against every mapped rule before finishing.

## Rule Index

Cross-cutting changes often need more than one rule file.

| Concern | Read |
| --- | --- |
| Query count, eager loading, indexes, large datasets | [`rules/db-performance.md`](rules/db-performance.md) |
| Subqueries, aggregates, complex ordering and query plans | [`rules/advanced-queries.md`](rules/advanced-queries.md) |
| Models, relationships, scopes, casts | [`rules/eloquent.md`](rules/eloquent.md) |
| Authentication, authorization, input safety, secrets, uploads | [`rules/security.md`](rules/security.md) |
| Form Requests and validation rules | [`rules/validation.md`](rules/validation.md) |
| Controllers, route binding, resources, middleware | [`rules/routing.md`](rules/routing.md) |
| Schema changes, columns, foreign keys, indexes | [`rules/migrations.md`](rules/migrations.md) |
| Jobs, retries, uniqueness, batches, Horizon | [`rules/queue-jobs.md`](rules/queue-jobs.md) |
| Cache lifetime, invalidation, locks, memoization | [`rules/caching.md`](rules/caching.md) |
| Outbound requests, retries, timeouts, fakes | [`rules/http-client.md`](rules/http-client.md) |
| Exceptions, reporting, rendering, log context | [`rules/error-handling.md`](rules/error-handling.md) |
| Events and notifications | [`rules/events-notifications.md`](rules/events-notifications.md) |
| Mailables and mail assertions | [`rules/mail.md`](rules/mail.md) |
| Scheduled tasks and overlap protection | [`rules/scheduling.md`](rules/scheduling.md) |
| Collections, lazy iteration, bulk operations | [`rules/collections.md`](rules/collections.md) |
| Blade components, attributes, composers | [`rules/blade-views.md`](rules/blade-views.md) |
| Environment values and application configuration | [`rules/config.md`](rules/config.md) |
| Naming, helpers, file boundaries, PHP style | [`rules/style.md`](rules/style.md) |
| Actions, services, dependencies, application structure | [`rules/architecture.md`](rules/architecture.md) |

## Decision Rules

- Prefer framework features and existing application abstractions over new helpers or dependencies.
- Avoid speculative abstractions. Extract code when it creates a clear domain boundary, removes meaningful duplication, or makes behavior independently testable.
- Keep database access out of Blade views and prevent hidden N+1 queries across controllers, resources, jobs, and serialization.
