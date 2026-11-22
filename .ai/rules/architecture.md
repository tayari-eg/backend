---
paths:
  - app/**
  - routes/**
  - config/**
  - database/migrations/**
  - tests/**
---

# Backend Architecture

## Domain-oriented structure (not full DDD)

```
app/Domains/{Accounts,Tayars,Catalog,Pricing,Orders,Payments,Wallet,Incentives,Support}/
    Models/  Actions/  Enums/  Events/  Listeners/  Data/  Policies/
app/Http/Controllers/Api/V1/{Public,Auth,Client,Tayar,Admin}/
app/Http/Requests/Api/V1/{Public,Auth,Client,Tayar,Admin}/
app/Http/Resources/Api/V1/{Public,Auth,Client,Tayar,Admin}/
```

The `app/Domains/*` skeleton above already exists, with a `.gitkeep` in each empty folder. Put every new file in the matching domain folder (remove the `.gitkeep` once the folder has a real file). `Data/` holds simple DTOs, only when needed.

Resources used by more than one side (e.g. `MediaResource`) live at the `app/Http/Resources/Api/V1/` root, not in a side folder.

Approved folders outside `app/Domains` and the HTTP side folders:

- `app/Rules`: validation rules (`php artisan make:rule`), e.g. `EgyptianMobileNumber`.
- `app/Http/Requests/Concerns`: traits shared by FormRequests (`php artisan make:trait`), e.g. phone normalization and pagination.
- `app/Models/Concerns`: traits shared by models in more than one domain (`php artisan make:trait`), e.g. `HasTranslatedNameSearch` for the translatable `name` scopes.
- `app/Contracts`: interfaces for swappable infrastructure, e.g. `SmsSender`.
- `app/Services/Sms`: the SMS drivers behind `SmsSender`, picked by `config('tayari.sms.driver')`.

## API versioning

- All API routes are versioned in the URL: `/api/v1/...`. No unversioned API routes.
- One API, split by who uses it, not by platform. React web and the Flutter app call the same endpoints:
  - `/api/v1/auth/...`: OTP, login, logout (everyone)
  - `/api/v1/client/...`: customers and businesses, web and app
  - `/api/v1/tayar/...`: the Tayar app
  - `/api/v1/admin/...`: the admin panel
- Route files are `routes/api/v1/{public,auth,client,tayar,admin}.php`, registered in `bootstrap/app.php` inside `withRouting(then: ...)` with prefix `api/v1`, name prefix `api.v1.`, and a name per file (`public.`, `auth.`, `client.`, `tayar.`, `admin.`). `public.php` holds unauthenticated endpoints such as `app-config` and has no URL prefix; the others also get their URL prefix. There is no `routes/api.php`. See the laravel-best-practices `rules/routing.md` for the code.
- **Only the HTTP layer is versioned:** `app/Http/Controllers/Api/V1/...`, `app/Http/Requests/Api/V1/...`, `app/Http/Resources/Api/V1/...`. `app/Domains` (Models, Actions, Enums, Events) is never versioned; all versions call the same Actions.
- **v2 only for breaking changes:** a removed or renamed response field, a changed field type, a new required parameter, or changed behaviour old apps can't handle. New endpoints, new response fields and new optional parameters stay in v1.
- **When v2 is needed:** add `routes/api/v2/*.php` registered the same way, and copy only the changed controllers, requests and resources into `V2`. Unchanged v2 routes point to the V1 controllers. v1 stays until most app users have updated.
- **Headers:** clients send `X-Platform` (`web` | `android` | `ios`); the Flutter app also sends `X-App-Version`. `GET /api/v1/app-config` (public, route name `api.v1.public.app-config`) returns `min_supported_version` and `latest_version` per platform (`android`, `ios`) plus the server time in UTC. There is no `force_update` flag: an app below `min_supported_version` must update. A middleware returns `426 Upgrade Required` for app versions below the minimum. Log `X-App-Version` to track old-version usage.
- **Responses** always go through API Resources (never return a model directly). Every version uses one error format: `{ "message": ..., "errors": {...} }`.
- The same Sanctum token works for every version.
- **API docs:** every new endpoint needs a one-line Scramble summary on its controller method, and must be added to the Postman docs: its documentation with the full template in `docs/postman/endpoints.php`, and its app(s), folder, name and screen in `docs/postman/{flutter,website,admin}.php` (or `not_used_directly` in `docs/postman/collection.php` with the reason). These docs files are not config: `postman:export` loads them with `require`, never through `config()`. Every change to the API or to `docs/postman` must add an entry at the top of `docs/postman/changelog.php` with a raised version: **major** for a new API version (`/api/v2`) or a breaking change, **minor** for new endpoints, new fields, new folders or new screens in the docs, **patch** for wording fixes, clearer docs, or corrected limits or examples. Then run `php artisan postman:export` and commit the updated `postman/` files (a test fails when they differ from a fresh export).
- **Changelog lines:** every line in `docs/postman/changelog.php` starts with a tag, then the method and full path, ` — `, what changed, and the apps it affects (Website, Admin panel, Flutter client mode, Flutter tayar mode); one endpoint per line. `Added:` a new endpoint; `Changed:` an existing endpoint behaves differently but old clients keep working; `Breaking:` an existing client can break (a renamed or removed field or value, a field that becomes required, a changed response shape or success status), and the line says what each app must change; `Removed:` an endpoint removed; `Docs:` documentation only. Every change to an endpoint that already exists on `main` needs its own tagged line, found by comparing with `git diff main`. `Breaking:` means a major version, unless it happens before launch and the line starts with `Breaking (before launch):`. `postman:export` fails on a line without a tag.

## Thin controllers

A controller validates with a FormRequest (`validation.md`), calls one Action, and returns a Resource. No business logic in controllers.

## One Action per business operation

One Action = one business operation (e.g. `CreateOrderAction`, `CalculateShippingFeeAction`, `AcceptOrderAction`, `DeliverOrderAction`), with a single public `handle()` method. Actions can be reused from any client's controller, any API version, a job, or a command.

## Side effects in Listeners

Side effects (notifications, bonus calculation, cached counters) go in Listeners for domain events (e.g. `OrderDelivered`), not inside the main Action. Dispatch events after the transaction commits.

Listeners in `app/Domains/*/Listeners` are discovered automatically: `bootstrap/app.php` calls `->withEvents(discover: [__DIR__.'/../app/Domains/*/Listeners'])`. Don't register them again by hand, or they run twice.

## Enums own their transitions

Status enums own their allowed transitions (e.g. `OrderStatus::canTransitionTo()`). See `domain-rules.md`.

## Constants and settings

- No global `app/Constants` folder.
- Fixed sets of values are PHP backed enums inside their domain: `app/Domains/{Domain}/Enums` (e.g. `OrderStatus`, `PaymentMethod`, `ShippingPaidBy`). Enums can hold behaviour (e.g. `OrderStatus::canTransitionTo()`, `label()`).
- Business numbers that may change but aren't edited by admins go in `config/tayari.php`, read with `config('tayari.…')`, and backed by env values where useful (e.g. OTP expiry minutes, OTP max attempts, rank downgrade grace days, daily withdrawal hour).
- Values admins edit (prices, campaigns, ranks, categories, reasons) live in database tables.
- A number used by only one class can be a `private const` in that class.
- No magic strings or numbers for statuses, types or limits anywhere in the code.

## Eloquent directly

Use Eloquent directly. No repository classes or interfaces.

## Cross-domain work

A domain may read another domain's models. Bigger cross-domain work, and every write, goes through that domain's Actions or Events (see Scalability).

## Money, translations, files

- Money is stored as `decimal(10,2)` and calculated with brick/math (`BigDecimal`), never float (`domain-rules.md`).
- Lookup names (categories, modes, business types, vehicle types, reasons, ranks, titles) use spatie/laravel-translatable.
- Files only through Media Library collections on named disks (see Scalability).

## Environments

**Phase 1 (about one month): shared hosting (GoDaddy).**
- No Docker, no Redis, no long-running processes.
- `QUEUE_CONNECTION=database`, `CACHE_STORE=database`.
- One cron entry runs `php artisan schedule:run` every minute; the scheduler runs `queue:work --stop-when-empty`.
- The scheduled queue worker is for the shared host only. In Phase 2 it is switched off with `QUEUE_WORK_FROM_SCHEDULER=false` (`config('tayari.queue.work_from_scheduler')`), because Horizon runs the workers.
- No websockets: the Tayar app sends its location every few seconds and clients poll for it.

**Phase 2 (MVP): VPS with Docker** running PostgreSQL + PostGIS, Octane on FrankenPHP, Redis, Horizon (queue workers), the scheduler, and Reverb.
- `storage/` is a persistent volume.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, live location through Reverb.

**Code must work in both phases without changes:** drivers and disks come only from `.env`/config, never hard-coded.

## Portable SQL

The database moves from MySQL 8 to PostgreSQL + PostGIS, so every query must run on both:

- Eloquent and the query builder only. No raw SQL that depends on one engine.
- No index hints (`FORCE INDEX`, `USE INDEX`, `IGNORE INDEX`).
- No MySQL-only functions (e.g. `IFNULL`, `GROUP_CONCAT`, `FIELD()`, `DATE_FORMAT`) and no `JSON_*` raw SQL. Use the query builder's JSON helpers (`where('options->key', ...)`, `whereJsonContains()`).
- Text search uses `whereLike()` / `orWhereLike()`, never raw `LIKE`/`ILIKE`.
- **The one exception:** for translatable JSON columns, a case-insensitive match may use `lower()` on the grammar-wrapped JSON path (`$query->getQuery()->getGrammar()->wrap('name->en')`) with `like` or `=` and bound parameters, inside a model scope, because MySQL compares JSON text case-sensitively. Everything else still uses `whereLike()`.
- **Distance math:** the nearest-tayar scope (`TayarProfile::nearestOnline()`) may use `radians`, `sin`, `cos`, `acos` and `least` in raw expressions with bound parameters; both engines have them. Moving to PostGIS later only changes that scope.
- JSON columns are `json()`, not `jsonb()`. Migrations use the Schema builder only.

## Octane safety

Phase 2 runs on Octane, so the app stays in memory between requests. Write code that is Octane-safe from day one:

- Request, user and locale state lives only in scoped bindings (`$this->app->scoped(...)`), the request itself, or Laravel's `Context`.
- Nothing request-specific in static properties, singletons, or objects a singleton holds.
- No in-memory caches that grow across requests (static arrays, memoized maps on singletons). Use the cache store instead.

## Scalability

- **Actions are a domain's public entry point.** A domain may read another domain's models, but must not write another domain's tables directly; it calls that domain's Action (e.g. Orders calls Wallet's `CreditWalletAction`, never inserts `wallet_transactions` itself).
- **Slow or external work runs on queues**, never inside the request: notifications, WhatsApp/SMS, media conversions, nightly rank recalculation, bonus calculation.
- **OTP SMS is the one exception:** it is sent inside the request, so the plain code never sits in the `jobs` table and arrives right away. Every other SMS and notification goes through the queue.
- **Files always go through Laravel disks by name**, never raw paths. Personal documents (national ID, criminal record, licences) use the private `local` disk and are served only by temporary signed URLs or an authorised download route. Public images (order photos, icons) use the `public` disk. Disk names come from config/env, so moving later to an S3-compatible store (e.g. Hetzner Object Storage) is only an `.env` change plus copying the existing files.
- **The API is stateless** (tokens), so more servers can be added later.
- **Location:** the production database engine only stores a Tayar's last known position, never a full location history from every update.
- **Queries:** every new query filter or sort needs a supporting index. Every list endpoint is paginated (cursor pagination for long lists). Lazy loading is prevented outside production (`Model::preventLazyLoading(! app()->isProduction())`).
- **Values that change** live in config or the database (see Constants and settings).
- **Read-heavy lookups** (categories, modes, pricing rules, ranks) are cached, and the cache is cleared when they change.
- **Tests mirror the structure:** `tests/Feature/Api/V1/{Public,Shared,Auth,Client,Tayar,Admin}/...`, `tests/Feature/Domains/{Domain}/...` for Action tests that need the database, `tests/Unit/Domains/{Domain}/...` for tests without it, and `tests/Unit/Services/{Service}/...` for unit tests of `app/Services`, and `tests/Feature/Console/...` for Artisan commands. `Public/` covers unauthenticated endpoints (`routes/api/v1/public.php`); `Shared/` covers behaviour every v1 route shares (middleware, error format).

## Migrations

Migrations are always created with `php artisan make:migration`, never hand-written (`generators.md`).
