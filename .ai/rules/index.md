# Project Rules Index

**Read the matching rules before editing.** Before you plan or edit, find every row whose paths match the files in scope and read that rule file. Then run `grep -rin '<keyword>' .ai/rules` to catch anything a path match misses. Each rule file's `paths:` frontmatter lists the exact globs it covers.

| Rule file | Applies to | Read it when |
| --- | --- | --- |
| [debugging.md](debugging.md) | all files | Investigating a bug: confirm the root cause with evidence before editing; rule out simple causes first |
| [scope.md](scope.md) | all files | Starting any change: confirm scope, make the minimal change, no extra scope |
| [architecture.md](architecture.md) | `app/**`, `routes/**`, `config/**`, `database/migrations/**`, `tests/**` | Adding or moving backend code: domain folders, API versioning, thin controllers, Actions, Listeners, constants and settings, environments, scalability |
| [domain-rules.md](domain-rules.md) | `app/**`, `database/**`, `routes/**`, `tests/**` | Touching money, wallets, payments, order statuses, codes/OTPs, ranks, bonuses or business time |
| [validation.md](validation.md) | `app/Http/Requests/**`, `app/Http/Controllers/**`, `app/Domains/**/Actions/**` | Adding or changing endpoint input: FormRequests under `app/Http/Requests/Api/V1/{Auth,Client,Tayar,Admin}` |
| [query-changes.md](query-changes.md) | `app/Domains/**`, `app/Jobs/**`, `app/Console/Commands/**`, `app/Http/Controllers/**`, `routes/console.php`, `database/migrations/**` | Rewriting a query: prove the same rows, call out condition changes, no index hints |
| [sql.md](sql.md) | `app/**`, `database/**`, `routes/**`, `tests/**` | Writing any query: verify columns against migrations or a sample row first |
| [code-comments.md](code-comments.md) | `app/**`, `database/**`, `routes/**`, `tests/**` | Writing comments: short, only for the non-obvious why |
| [generators.md](generators.md) | `app/**`, `database/**`, `tests/**` | Creating a file: always `php artisan make:*`, migrations included |
| [config-env.md](config-env.md) | `app/**`, `routes/**`, `config/**`, `resources/views/**` | Reading an environment value: `env()` only in config files |
| [tests.md](tests.md) | `tests/**`, `phpunit.xml`, `database/factories/**` | Writing or running tests: separate testing database on the production engine, related tests only, PHPUnit, factories, never delete tests |
| [dependencies.md](dependencies.md) | `composer.json`, `composer.lock`, `package.json`, `package-lock.json` | Any package change: no dependency changes without approval |

## Skills

| Skill | Use when |
| --- | --- |
| [laravel-best-practices](../skills/laravel-best-practices/SKILL.md) | Writing, reviewing or refactoring Laravel PHP code: controllers, models, migrations, requests, policies, jobs, scheduled commands, Actions, Eloquent queries, caching, validation, error handling. Rules in `.ai/rules/` take precedence over the skill. |
| [ai-code-review](../skills/ai-code-review/SKILL.md) | Reviewing a GitHub PR, a branch or a diff (feature/fix PRs into `main` until a `develop` branch exists; then into `develop`, with release PRs from `develop` into `main`). |

## Adding a rule

Durable project rules belong here in the repo, not in an agent's private memory or notes. To record one, add a Markdown file to `.ai/rules/` (or extend an existing one) with `paths:` frontmatter listing the globs it covers, then add a row to the table above.
