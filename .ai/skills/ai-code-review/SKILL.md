---
name: ai-code-review
description: "Use this skill whenever a developer asks an AI assistant to review Tayari backend code (the Laravel API for the delivery app's React website and Flutter client/Tayar apps): a GitHub PR, a branch, or a diff. It runs in a fresh session, requires a brief first, picks the right base for Tayari's flow (feature/fix PRs into `main` until a `develop` branch exists for staging; then feature/fix PRs into `develop` and release PRs from `develop` into `main`), and reports only defects introduced by the change, with extra scrutiny on money/wallet, order statuses, delivery codes and OTPs, client/Tayar/admin access, private documents, Cairo time, translations and live location. Every finding has a severity (blocker/major/minor/nit), a confidence level, a file:line, and a concrete fix. The output is a copy-pasteable PR comment, and developers answer each finding in the PR comment thread (relevant/irrelevant/fixed/resolved). Trigger on 'review my PR', 'review this branch', 'code review', 'check this diff', 'is this safe to merge', 'review the release PR', 're-review'."
license: MIT
metadata:
  author: tayari
  adapted-from: follow.it .ai/skills/ai-code-review
---

# AI Code Review (Tayari)

Review a change the way a careful senior Tayari reviewer would. Measure it against the brief, find what it is likely to break, and say nothing you cannot back with a file and line.

This skill defines the review *process*. Project facts (stack, layout, commands) live in `CLAUDE.md` / `AGENTS.md` at the repo root, and project rules live in `.ai/rules/` (see §2 step 5). Read them, but check them against the code: treat them as conventions to verify against actual behavior, not as proof that an implementation is wrong.

## 0. Ground rules

- **Fresh session.** Use no chat history, memory, or "what we discussed". Repo files are fair game. A re-review also runs in a fresh session (§9).
- **Read-only.** Do not edit code, run formatters in fix mode, run migrations, commit, `git fetch`, or push. Review the refs already on disk, and report their commit age and last fetch time (§2 step 3).
- **No GitHub writes** unless the developer explicitly asks in this session (see §10). Never ask for a token or password.
- When you cannot verify something, say so. Never present an assumption as a fact.
- **zsh.** Quote `ref:path` arguments as `"${ref}:path"`. zsh expands `:a` and similar as modifiers, so `$ref:app/x` and `"$ref:app/x"` both break.

## 1. Require a brief

Before you read the code, get the objective from the PR description, a linked issue or task, or the developer: *what should this change do, and why*. If there is none, or it is vague ("fix stuff", "refactor"), stop and ask:
*"What is this PR meant to accomplish? Paste the task/PR description."*
Do not reverse-engineer intent from the diff. **No brief → no review.** The brief also drives the scope check (§4).

## 2. Identify the PR type and the correct base

**Until a `develop` branch exists for staging, `main` is the base for every feature/fix branch:** `feature/<x>` and `fix/<x>` are cut from `main` and target `main`, and there are no release PRs. Check with `git ls-remote --heads origin develop` (or `git branch -a`). Once `develop` exists, Tayari uses one integration branch and one release branch:

| PR | Source branch | Target | Purpose |
| --- | --- | --- | --- |
| **Feature/fix PR** | `feature/<x>` or `fix/<x>` (cut from `develop`) | `develop` | integrate a change |
| **Release PR** | `develop` | `main` | ship a release |

1. **Get the PR.** Use `gh`, read-only: `gh pr view <number> --json number,title,body,baseRefName,headRefName,headRefOid,commits,files` and `gh pr diff <number>`. If `gh` isn't available or authenticated, ask for the PR description and the diff. A branch already on disk can supply the diff, and §1 still applies to the description. Never invent PR content you have not seen (fallbacks for the PR number and title: §10).
2. **Find the target.** The declared PR target (`baseRefName`) wins. If it isn't given: a `feature/` or `fix/` branch → `main` while no `develop` branch exists, otherwise `develop`; `develop` as the source → `main`; otherwise **ask**. Wrong target is a **blocker** only when the target is known: once `develop` exists, a feature/fix branch targeting `main` would skip `develop` and ship unintegrated code.
3. Prefer `origin/<base>` over local `<base>`. Record each ref's SHA and commit age: `git log -1 --format='%h %cr' origin/<base>`. Commit age does not show how recently the refs were fetched, so also record the last fetch time: `stat -f '%Sm' .git/FETCH_HEAD` (macOS) or `stat -c '%y' .git/FETCH_HEAD` (Linux). If the last fetch is older than a day, continue the review, mark the base as possibly stale in the PR line, and ask the developer to fetch. Do not pause, and do not fetch yourself. If `.git/FETCH_HEAD` can't be read (e.g. in a linked worktree), say the fetch time is unknown.
4. Review the **merge-base diff**, not the full history or just the last commit:
   ```bash
   git log --oneline origin/<base>..feature/<x>         # commits in the PR (<base> is main until develop exists)
   git diff --stat origin/<base>...feature/<x>           # three dots = since merge base
   git diff origin/<base>...feature/<x>
   ```
   If you were only given pasted diff text, review that. Say that branch-level checks (references, tests) were not possible.
5. **Read the rules.** Once you know the changed files, read `.ai/rules/index.md`, then every rule file whose paths match them, and always `.ai/rules/domain-rules.md` and `.ai/rules/architecture.md`. Then run `grep -rin '<keyword>' .ai/rules` for each changed class name and each of the feature's key terms, to catch anything a path match misses. Use `.ai/skills/laravel-best-practices/SKILL.md` where relevant; the rules take precedence over it. A finding that breaks a rule names the rule file. Bare `<name>.md` references in this skill are files in `.ai/rules/`.

## 3. Release PRs and references

**Release PR (`develop` → `main`).** The release should carry only changes that already went through reviewed feature/fix PRs into `develop`.

```bash
git log --oneline origin/main..origin/develop          # what this release ships
gh pr list --base develop --state merged --limit 50 --json number,title,mergeCommit
```

- A commit in the release that didn't come through a merged PR into `develop` (pushed directly to `develop`) is **major**: it was never reviewed. List it.
- A commit on `main` that is missing from `develop` (a hotfix not merged back) is **major**: the next release could revert it. Check with `git log --oneline origin/develop..origin/main`.
- For each migration in the release, apply §5.1 against `origin/main`, because that is what production has run.
- Do not re-review every merged PR line by line. Review the net release diff for cross-PR interactions (two PRs touching the same wallet, status or route code), and point to the original PR for anything already reviewed there.

On **every** PR, check that everything the change *references* exists on the target branch: classes, routes, columns, config keys, enum cases, translation keys.

Grep the PR head (target + this diff), not only `origin/<target>`. The head includes symbols the PR adds, and shows references left behind to symbols it removes.

```bash
git grep -nE '<Symbol|route|column|config key>' <head branch> -- app routes database config lang resources   # PR head
git grep -nE '<Symbol|route|column|config key>' origin/<target> -- app routes database config lang resources  # the target, which may have moved past the merge base
```

The grep cannot find route names generated by `Route::resource` / `apiResource` (e.g. `client.orders.show`), so for those, read the route file at the PR head. Record that the reference check ran, and its result, under *Checks run*.

A referenced symbol, route, column, or config key that is missing at the PR head, or missing on `origin/<target>` when the PR does not add it itself, is a **blocker**.

## 4. Core review

These passes apply to every review, whatever the flags in §5.

### 4.1 The two questions
1. **Does it satisfy the brief?** Is the change complete and correct against what it was asked to do?
2. **What could it break?** Which existing behavior, callers, or data could regress? Remember that the React website and the Flutter client and Tayar apps all consume the same API.

### 4.2 Scope check
Map every changed hunk to the brief. Flag what does not map:

- drive-by refactors, renames, or logic changes the brief doesn't mention;
- formatting churn on lines the change didn't otherwise touch (usually a directory-wide Pint run);
- stray files: `docs/*.md` working notes, `.env*`, `phpunit.xml(.bak)`, `composer.lock` without a matching `composer.json` change, debug code (`dd(`, `dump(`, `ray(`, `Log::debug` left in hot paths), commented-out blocks;
- changes to an already-shipped migration (see §5.1).

Out-of-scope changes are **minor**. They are **major** when they touch a §5 flagged area. Also check the reverse: **does the change fully deliver the brief?** A missing piece is a finding.

### 4.3 Correctness
Trace the changed code paths end to end. Look for logic errors, off-by-one, wrong types, unhandled nulls, altered behavior for existing callers, removed or changed error handling, and broken error paths.

### 4.4 Cleanliness
Check code **in the diff only**, and report only when it affects correctness, maintainability, or the brief:

- **DRY:** no duplicated logic; reuse existing abstractions and framework features.
- **KISS:** no speculative abstraction or complexity the brief does not justify.
- **Single responsibility:** a class or method does one thing.
- **No verbose code:** nothing longer than the intent requires.
- **Architecture** (`architecture.md`): thin controllers (FormRequest → one Action → Resource), one business operation per Action with a single `handle()`, side effects in Listeners, no repository classes.
- **Generated, not hand-written** (`generators.md`; migrations: §5.1). A diff cannot prove how a new file was created, so compare it to its siblings and flag only clear signs, such as a wrong directory or namespace, or a missing base class or trait that both the stub and the siblings have. Severity is **minor**.

Do not demand refactors of existing code (§8).

## 5. Flags and Tayari checklist

Open the report with a flag for each area the diff touches. A flag means *scrutinize harder*, not *broken*. For each flag, work through its checklist. Every flag maps to a checklist, and every checklist has a flag.

| Flag | Touches | Checklist |
| --- | --- | --- |
| 🗄️ Migration | `database/migrations/**` | §5.1 |
| 🐢 Queries | Eloquent or query builder changes, `DB::`, raw SQL, eager loading, model `$with` | §5.2 |
| 🧊 Cache | `Cache::remember/tags/flush/memo`, cached counters | §5.3 |
| 🌐 API contract | `app/Http/Resources/Api/V*`, `routes/api/v*/*.php`, `routes/api.php`, `bootstrap/app.php` routing, `docs/postman/**`, response shape/status codes, error format (consumed by the React website and the Flutter apps) | §5.4 |
| 💰 Money / wallet | `app/Domains/{Wallet,Payments,Pricing,Incentives}`, `wallets`, `wallet_transactions`, `bonus_campaigns`, any amount, fee or balance | §5.5.1 |
| 📦 Order status | `app/Domains/Orders`, `OrderStatus`, `order_status_histories`, accept/pickup/deliver/cancel Actions | §5.5.2 |
| 🔢 Codes / OTP | delivery or refusal codes, OTP send/verify, their routes and rate limiters | §5.5.3 |
| 🔐 Access | Sanctum, route files (`auth`, `client`, `tayar`, `admin`), Policies, FormRequest `authorize()`, Spatie roles and permissions | §5.5.4 |
| 🪪 Files | Media Library collections, disks, national ID, criminal record, licences, signed URLs | §5.5.5 |
| 🕚 Time | the 11 pm withdrawal rule, `available_at`, campaign start/end, "today", nightly rank jobs, `routes/console.php`, `app.business_timezone` | §5.5.6 |
| 🌍 Translations | translatable JSON columns, `HasTranslations`, `__()`, `lang/**` | §5.5.7 |
| 📍 Location | Tayar live location storage, endpoints, broadcasts | §5.5.8 |
| 🔣 Constants | new string/number literals for statuses, types or limits, `app/Domains/*/Enums`, `config/tayari.php`, `const` declarations | §5.5.9 |
| 🧱 Domain boundaries / scalability | writes from one domain to another domain's tables, slow or external work in a request, file paths and disks, cache/queue/broadcast drivers, list endpoints, new filters or sorts | §5.5.10 |
| ✅ Validation | `app/Http/Requests/**`, or a new or changed input read in a controller or action | §5.6 |
| 🔄 Queue / events | `app/Jobs`, `app/Domains/*/Events`, `app/Domains/*/Listeners`, observers, broadcasting | §5.7 |
| 🧪 Tests | `tests/**`, `phpunit.xml`, `database/factories/**` | §5.8 |
| ⚙️ Env / config | `.env.example`, `config/**`, `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `env()` anywhere outside `config/*.php` | §5.9 |

Each item below is something that follows directly from how Tayari is built, or what it must never get wrong.

### 5.1 Migrations
- **Generated, not hand-written** (`generators.md`). Signs of a hand-written file: the name doesn't match `^\d{4}_\d{2}_\d{2}_\d{6}_[a-z0-9_]+\.php$`; the time part is suspiciously round (`000000`, `000001`, `100000`, `120000`); a date earlier than the newest migration on the base branch; a named class instead of `return new class extends Migration`. Check only migrations *added in this diff*. Package stubs from `vendor:publish` (Sanctum, Media Library, Permission) legitimately carry the publish time. Severity is **minor**. It becomes **major** when the timestamp sorts before migrations already run on `main`, because it then runs out of order.
- **Editing a shipped migration** (`generators.md`; one that already exists on `origin/main`) is a **blocker**.
- **`down()`** exists and reverses `up()` exactly: drops what `up` added, restores the original column type/default/nullability on `change()`, and names the same index. A missing or empty `down()` is **minor**. A destructive `up` with no way back is **major**.
- **Indexes.** New `where`/`orderBy`/join columns in the same PR need an index. Name long composite indexes explicitly and keep index names within 63 characters, which fits both MySQL (64) and PostgreSQL (63). Forced indexes: see `query-changes.md`.
- **Money columns** are `decimal(10,2)`, never `float`/`double` (`domain-rules.md`): **major**. Idempotency needs a unique index (§5.5.1).
- **Large or hot tables** (`users`, `orders`, `order_status_histories`, `wallet_transactions`, location tables). Check for a `NOT NULL` column without a default, a column type change, a rename, or a data backfill in the same migration without `chunkById`. These lock or time out on production: **major**. Flag Eloquent models used inside a migration (schema drift).

### 5.2 Queries and performance
- **N+1:** relations accessed in loops, in Resources (`$this->relation` without `whenLoaded`), or in notifications, without eager loading. Name the loop and the relation.
- Unbounded reads: `->get()`/`->all()` on growing tables in requests or jobs. Use `paginate`, `chunkById`, or `cursor`. Look for `per_page`/`limit` taken from input with no `max:` rule. An unpaginated list endpoint is **major** (cursor pagination for long lists, `architecture.md`).
- A new query filter or sort with no supporting index (in this PR or already migrated) is **major** on a growing table, **minor** otherwise.
- `->count()` where `exists()` is meant.
- New `ORDER BY` on non-indexed columns or mixed directions. Growth in model `$with` defaults.
- Raw SQL (`DB::raw`, `whereRaw`, `orderByRaw`, `selectRaw`) with interpolated input instead of bindings: **blocker** (SQL injection).
- A rewritten query or changed condition: apply `query-changes.md`, and ask for the proof it requires in the PR.

### 5.3 Caching
- A new broad `Cache::tags([...])->flush()` is **major** if it sits on a per-request write path.
- Cache keys must vary on every input that changes the output: user, role/side (client/Tayar/admin), query params, `per_page`, locale (`ar`/`en`). Caching user-specific or permission-dependent data under a shared key is a **blocker** (it leaks data across users).
- Writes that change cached data must invalidate it. Check the TTL is sane. A cached wallet balance or counter read as the source of truth for a payout is **major**: the ledger is the truth (§5.5.1).

### 5.4 API responses (the React website and the Flutter apps depend on them)
- **Versioning** (`architecture.md`, laravel-best-practices `rules/routing.md`): every API route sits under `/api/v1/...` (or a later version) in `routes/api/v{n}/{public,auth,client,tayar,admin}.php`. An unversioned API route (e.g. a new route in `routes/api.php`) is **major**. Controllers, requests and resources for a version sit under `Api/V{n}`; `app/Domains` is never versioned, and versioned code calls the same Actions.
- **A breaking change to an existing version** (a removed or renamed field, a changed field type, a new required parameter, behaviour old apps can't handle) is a **blocker**: it belongs in a new version, with only the changed controllers/requests/resources copied into `V{n+1}`. New endpoints, new fields and new optional parameters in v1 are fine.
- **A model or model collection returned without an API Resource** is **major**. Errors use the single format `{ "message": ..., "errors": {...} }` in every version; a different shape is **major**.
- A changed status code or pagination shape breaks a client in the same way as a removed field; mobile releases lag, so old app versions keep calling the old shape. Treat it as a breaking change (above).
- Money is returned as a string (from `decimal`), never a float.
- Privacy: embedded users stay limited. No national ID, documents, tokens, codes, OTPs, or another party's phone or location beyond what the order needs (§5.5.8).
- Use `Resource::make`/`Collection` like sibling endpoints. When verifying, reason via `resolve()`/HTTP, not `toArray()` (`MissingValue`).
- **Docs changelog** (`architecture.md`, API docs): a change to routes, request or response shapes, or `docs/postman/**` without a new entry at the top of `docs/postman/changelog.php` with a raised version is a finding (**minor**). So is a wrong bump: **major** for `/api/v2` or a breaking change, **minor** for new endpoints, fields, folders or screens, **patch** for wording, clearer docs, or corrected limits or examples. The updated `postman/` files must be in the same PR.

### 5.5 Tayari risk checklist

#### 5.5.1 Money
- Every wallet change is a row in `wallet_transactions`, written **inside a DB transaction** together with the cached `wallets.balance` update. A balance change without its ledger row, or the two written outside one transaction, is a **blocker**.
- Unique indexes stop paying the same order or bonus twice (e.g. one earning row per order per wallet, one bonus row per campaign and order/target). A retry, double tap, or re-run job that can pay twice is a **blocker**. Lock the wallet row (`lockForUpdate()`) when reading the balance to decide.
- No `float` for money: `decimal(10,2)` columns, `decimal:2` casts, and brick/math (`BigDecimal`) in code, with an explicit scale and `RoundingMode` when dividing. `+`, `-`, `*`, `round()` on amounts, or a `float` type hint, is **major**.
- Payment flows follow `domain-rules.md`: receiver pays before delivery, delivered → business and Tayar shares in one transaction, not delivered/cancelled after payment → refund.

#### 5.5.2 Order statuses
- Only allowed transitions: `looking_for_tayar → pending → picked_up → ongoing → delivered | not_delivered`; `cancelled` only before pickup. The check lives in the status enum (`OrderStatus::canTransitionTo()`), not ad hoc `if`s. A path that skips or reverses a status is **major**; one that moves money on a wrong transition is a **blocker**.
- Every change writes a row to `order_status_histories`, in the same transaction as the status update.
- Concurrency: two Tayars accepting the same order, or deliver racing cancel, must be prevented (row lock or a conditional `update ... where status = ?`).

#### 5.5.3 Codes and OTPs
- Delivery/refusal codes and OTPs are stored **only as hashes**, with an expiry and an attempt limit. A plain-text code in a column, log, response, or notification payload beyond the intended recipient is a **blocker**.
- OTP and code endpoints have a rate limit (`throttle` / `RateLimiter`). A missing limit is **major**.
- Compare with `Hash::check()` or `hash_equals()`, never `==`.

#### 5.5.4 Access
- Client tokens can't call `tayar/` routes and the other way round. A route in the wrong group, or a group missing its side check, is a **blocker**.
- A Tayar only sees offers or orders meant for him; a sender only sees their own orders. A missing ownership check (Policy or FormRequest `authorize()`) is a **blocker**.
- Admin actions check Spatie permissions. An admin action reachable without a permission check is a **blocker**.
- Mass assignment: `$request->all()` / `->fill($request->input())` instead of `validated()`.
- Guard confusion: the React website uses Sanctum SPA cookies and Flutter uses Bearer tokens. Check both paths.

#### 5.5.5 Files
- National ID, criminal record and licences go in a Media Library collection on a **private** disk, served only through temporary signed URLs. A public disk, a permanent URL, or a raw path in a response is a **blocker**.
- Files go through Laravel disks by name: personal documents on the private `local` disk, public images on `public`, with disk names from config/env. A raw filesystem path (`storage_path()`, `public_path()`, `file_put_contents`) or a hard-coded disk name in domain code is **major**: it breaks the later move to S3-compatible storage.
- Uploads validate type and size in the FormRequest.

#### 5.5.6 Time
- Everything is stored in UTC (`config('app.timezone')` is `UTC`). Business rules (the daily 11 pm withdrawal rule and `available_at`, campaign start/end, "today") are calculated in `config('app.business_timezone')` and converted to UTC to store or compare (`domain-rules.md`). A rule computed in UTC when business time is meant is **major** (it moves the cut-off by two or three hours). A hard-coded `'Africa/Cairo'` instead of the config key is **minor**.
- Scheduled tasks in `routes/console.php` that represent business times pass `->timezone(config('app.business_timezone'))`.

#### 5.5.7 Translations
- User-facing lookup lists (categories, modes, business types, vehicle types, reasons, ranks, titles) use translatable JSON columns (`HasTranslations`), with both `ar` and `en`.
- API messages use `__()` with keys present in both Arabic and English. A hard-coded user-facing string is **minor**; a key missing in one language is **minor**.

#### 5.5.8 Location
- A Tayar's live location is exposed only to the sender and the receiver of his **active** order, and stops after it ends. Any other exposure (a list endpoint, a broadcast channel without authorization, a past order) is a **blocker**.
- MySQL stores only the Tayar's last known position. Inserting a row per location update (a full history) is **major**.

#### 5.5.9 Constants and settings
- Statuses, types and other fixed sets are backed enums in `app/Domains/{Domain}/Enums`. A magic string or number for a status, type or limit (e.g. `'delivered'`, `5` attempts, `23` o'clock) is **minor**; it is **major** when it decides money or an order transition.
- No global `app/Constants` folder: **minor**.
- Business numbers that may change (OTP expiry and attempts, rank grace days, withdrawal hour) come from `config('tayari.…')`; admin-edited values (prices, campaigns, ranks, categories, reasons) come from the database. A hard-coded copy of either is **minor**. A number used by only one class may be a `private const`.

#### 5.5.10 Domain boundaries and scalability (`architecture.md`)
- **Direct cross-domain write:** a domain inserting or updating another domain's tables instead of calling its Action (e.g. Orders writing `wallet_transactions` instead of calling Wallet's `CreditWalletAction`) is **major**; it is a **blocker** when it bypasses a money rule (§5.5.1).
- **Slow or external work inside the request** (notifications, WhatsApp/SMS, media conversions, rank recalculation, bonus calculation) instead of a queued job or queued listener is **major**.
- **Hard-coded drivers:** a cache, queue, broadcast, session or filesystem driver/connection named in code (`Cache::store('redis')`, `->onConnection('redis')`, `Storage::disk('s3')`) instead of the configured default or a config value is **major**: the code must run unchanged on shared hosting (database drivers) and on the VPS (Redis).
- Server state that stops the API being stateless (local files as shared state, in-memory state across requests) is **major**.
- Read-heavy lookups (categories, modes, pricing rules, ranks) are cached and the cache is cleared when they change. A write path that changes a cached lookup without clearing it is **major**.
- New tests go under `tests/Feature/Api/V1/{Public,Shared,Auth,Client,Tayar,Admin}/...`, `tests/Feature/Domains/{Domain}/...` (Action tests that need the database), `tests/Unit/Domains/{Domain}/...` (no database) `tests/Unit/Services/{Service}/...` (unit tests of `app/Services`) or `tests/Feature/Console/...` (Artisan commands): **minor** if elsewhere.

### 5.6 Validation
- Where inputs are validated: see `validation.md`.
- Bounds: `max:` on strings and `per_page`, `Rule::enum` for enums, `decimal:` or numeric bounds on amounts, scoped `exists:`/`unique:` (ignore the current row on update, respect soft deletes).
- `prepareForValidation` casts (e.g. `$this->boolean(...)`) stay consistent with the rules.

### 5.7 Queues, events, listeners
- Jobs and listeners are idempotent under retry. Use `ShouldBeUnique`, locks, or unique indexes where double execution corrupts data (payouts, bonuses, rank recalculation).
- Side effects (notifications, bonus calculation, cached counters) live in Listeners for domain events such as `OrderDelivered`, not inside the main Action (`architecture.md`). Events dispatched inside a transaction use `ShouldDispatchAfterCommit` or `afterCommit()`, or the listener may not see the committed row.
- With `SerializesModels`, models are stored as IDs and re-queried in the worker, so they hold fresh data. Non-model data is a snapshot taken at dispatch. Load the relations a job needs in `handle()` with `loadMissing`.
- A listener that isn't registered never fires: check how the app registers listeners under `app/Domains/*/Listeners`.
- Broadcast payloads expose only what the receiver may see (§5.5.8).
- Tests usually run with `QUEUE_CONNECTION=sync`, so green tests do not prove async behavior.

### 5.8 Tests included in the PR (quality only)
- Each test must assert the behavior in the brief. Flag tests that are tautological, flaky, or assert the mock.
- Prefer HTTP-level tests (`getJson`/`postJson`, `assertJsonPath`/`assertJsonMissingPath`), acting as the right side (client, Tayar, admin).
- Money and status tests assert the ledger rows and history rows, not only the response.

### 5.9 Env, config, dependencies
- `env()` outside `config/*.php` is a finding (`config-env.md`). It is **major** when the value builds a link, a recipient, or a key. Otherwise it is **minor**. Once config is cached (`php artisan config:cache` or `optimize`, standard on production), `env()` outside `config/*.php` returns null unless the variable is a real process variable.
- A new `env()` key in `config/**` must also be in `.env.example`, with no real secret in it. Check that its default is safe when the variable is unset.
- Dependency changes need the approval `dependencies.md` requires. Ask where it is recorded if the PR doesn't say.

## 6. Checks you may run (and only these)

1. **Syntax:** `php -l <changed .php files>`, skipping `.blade.php` files. Report Blade files as not linted. When the branch isn't checked out, use `git show <ref>:<path> | php -l`.
2. **Formatter:** `./vendor/bin/pint --test <exact changed files>`. Never pass a directory, and never run without `--test`. Report the result once under *Checks run*. Pint output is not a finding. If the branch isn't checked out, report Pint and tests as not run, because they would check the wrong code. If it is checked out but the working tree is dirty, also report Pint and tests as not run, with that reason. "Dirty" means modified or staged tracked files only; untracked files don't count. Check with `git status --porcelain --untracked-files=no` (empty = clean).
3. **Related tests only:** follow `tests.md`. Also consider the matching `tests/Feature/Api/V1/{Public,Shared,Auth,Client,Tayar,Admin}/` folder for a changed controller. The test database must be a separate testing database on the same engine as production, one that the developer confirms in this session, so **ask before running anything**. If you cannot run a failing test on the base branch, report it as "fails; not verified whether pre-existing".
4. No PHPStan/Larastan is installed. Do not claim static-analysis coverage.

## 7. Evidence, severity, confidence

A finding names **the execution path or condition**, **the concrete consequence**, **the file:line**, and **a concrete fix** (code or an exact instruction). A finding that breaks a rule also names **the rule file**. Investigate as far as needed to verify, but report only defects introduced or exposed by this diff.

> Weak: "This query might be slow." Strong (hypothetical): "`FooResource::toArray` (`app/Http/Resources/FooResource.php:18`) reads `$this->owner->name`, but `FooController::index` paginates `Foo::query()` without `->with('owner')`, so each page of 15 runs 15 extra queries. Fix: add `->with('owner:id,name')` in the index query and use `whenLoaded('owner')` in the resource."

**Severity**: based on demonstrated impact, not worst case:

| Level | Meaning | Merge |
| --- | --- | --- |
| 🔴 **blocker** | security hole, data loss/corruption, money paid twice or lost, prod outage, shipped-migration edit, wrong target branch | must fix |
| 🟠 **major** | real bug or regression, broken API contract, unreviewed commit in a release, unsafe large-table migration, notable perf regression | fix before merge, or `resolved` with a reason |
| 🟡 **minor** | correct but fragile: missing `down()`, hand-written migration name, out-of-scope change, missing translation key | fix or justify |
| ⚪ **nit** | small clarity win directly in changed lines | optional, **max 3 per review** |

**Confidence**:

| Level | Meaning |
| --- | --- |
| **high** | verified: read the full call path, ran a test, or reproduced it |
| **medium** | strong code evidence, with one stated assumption you could not verify |
| **low** | plausible but unverified |

Filtering rules: **low** confidence never goes in the findings table. Put it under *Open questions*. **nit** requires **high** confidence. A **blocker** requires **high** confidence, or **medium** with the unverified assumption spelled out. One underlying defect is one finding: list the extra locations inside it.

**Verdict**: any blocker or major → **Changes requested**. Only minors → **Approve with minor fixes**. Only nits or nothing → **Approve**.

## 8. Do NOT flag

If you verify that a choice is intentional (from the brief, the PR, `.ai/rules/`, or a code comment) or safe, drop it instead of reporting it.

- Formatting or style that Pint handles (§6 reports Pint once).
- Pre-existing issues in untouched code, unless the diff makes them reachable or worse.
- Refactors, renames, or "better patterns" the brief didn't ask for. Existing local patterns in untouched code are not findings, but new or changed inputs still follow `validation.md`, even when a neighbor doesn't.
- Missing tests or docs, unless the brief asks for them. Test *quality* is in scope (§5.8).
- Missing types, docblocks, `final`, or strict types on code that follows its neighbors.
- Hypotheticals with no path in the code ("what if the DB is down").
- Legacy commented-out code the diff didn't add.
- Anything the developer previously marked `irrelevant` on this PR, unless that code changed (§9).

When a rule in `.ai/rules/` and the code disagree, report the mismatch under *Open questions*, naming both the rule file and the code location. Do not drop a finding just because the rule exists, and do not edit the rule.

## 9. Feedback on findings, and re-review

Every finding starts as `unanswered`. The developer answers each finding in the PR comment thread, using one of the other states. Developers do not edit the review comment itself.

| Value | Meaning | Who sets it |
| --- | --- | --- |
| `unanswered` | no reply yet | starting state of every finding |
| `relevant` | agreed, fix coming | developer; re-review, when a `fixed` finding is still present |
| `irrelevant` | false positive or not applicable, **with a one-line reason** | developer |
| `fixed` | fixed, **with the commit SHA**; not ready to merge until re-review verifies it | developer |
| `resolved` | closed: a `fixed` finding verified on re-review, or closed without a code change (accepted risk, follow-up issue, intended behavior) **with a reason or link** | re-review for a verified fix; developer, agreed by a human reviewer, for no code change |

The flow: unanswered → `relevant` → `fixed` → `resolved`, or unanswered → `fixed` → `resolved`, or unanswered → `resolved` with a reason, or unanswered → `irrelevant`. The PR is ready when no **blocker** or **major** is unanswered, `relevant`, or `fixed`.

**Re-review** happens when the developer asks for one. It also runs in a fresh session: the developer pastes the previous review comment and their replies (or you read them with `gh pr view <number> --comments`), and that text is the only history allowed. If you can't get it, ask for it. Each re-review is a new PR comment. Never edit or delete the earlier review comment.
1. Diff only what is new: `git diff <previous head SHA>..<branch>`, plus `git range-diff` if the branch was rebased.
2. In the *Previously flagged* section, classify each earlier finding that is `unanswered`, `relevant`, or `fixed`, checking it at the current head, including when there are no new commits:
   - **Fixed** (name the change) → `resolved`.
   - **Still present** (with the line) → keeps its state, except that a finding marked `fixed` goes back to `relevant`.
   - **Cannot tell from this diff** (say what is needed), only when the head can't show it → keeps its current state.
   - If a `fixed` reply cites a SHA that doesn't exist or isn't on the branch, say so in the Result cell and judge the finding at the current head.
3. List earlier `irrelevant` and `resolved` findings there as closed. Do not re-raise them, unless the code changed materially.
4. Keep the original finding numbers. Number new findings after them.
5. If several `irrelevant` reasons share a pattern, suggest a line to add to §8 in the summary. Do not edit this skill yourself.
6. Re-run the §3 reference check (and, on a release PR, the release checks) and record the result under *Checks run*.

The re-review verdict counts every finding that is still `unanswered`, `relevant`, or `fixed` (carried over) plus any new ones. Use "Approve, no findings" only when none of those remain. With no new commits, say so and keep the carried-over verdict.

Start the re-review comment like this, then continue with the §10 sections for the new commits. With no new commits, include only these §10 sections: *Findings* with "No new findings", *Checks run*, and *Not verified*.

````markdown
## 🤖 AI re-review: <PR title> (#<number>)

**Re-review of:** <link to the previous review comment> · **Commits covered:** `<previous head SHA>..<new head SHA>` (<n> commits)
**PR:** <feature/fix | release> · `<source>` → `<target>` · base `origin/<target>` @ `<sha>` (<commit age>; last fetch <time or "unknown">; <if older than a day: "may be stale, please fetch">) · head `<sha>`
**Verdict:** <Approve | Approve with minor fixes | Changes requested>, <n> blocker · <n> major · <n> minor · <n> nit

### Previously flagged
| # | Finding | Result | State |
|---|---|---|---|
| 1 | <title> | Fixed: <the change and SHA> | `resolved` |
| 2 | <title> | Still present: `path/File.php:42` | <`unanswered` or `relevant`> |
| 3 | <title> | Cannot tell from this diff: <what is needed> | <current state> |
| 4 | <title> | Closed earlier | `irrelevant` |
````

## 10. Output: the PR comment

Return the review as **one fenced markdown block** ready to paste as a PR comment on GitHub. Post it yourself only if the developer explicitly asks *in this session* and `gh` is already authenticated (write the block to a file and run `gh pr comment <number> --body-file <file>`). Never claim it was posted unless that succeeded. Keep it tight: no praise, no restating the diff.

````markdown
## 🤖 AI review: <PR title> (#<number>)

**PR:** <feature/fix | release> · `<source>` → `<target>` · base `origin/<target>` @ `<sha>` (<commit age>; last fetch <time or "unknown">; <if older than a day: "may be stale, please fetch">) · head `<sha>`
**Flags:** 💰 Money / wallet · 📦 Order status   _(or "none")_
**Verdict:** <Approve | Approve with minor fixes | Changes requested>, <n> blocker · <n> major · <n> minor · <n> nit
**Brief:** <Met | Partly met | Not met>, <one line why>
**Release check:** <✅ all commits came through reviewed PRs into `develop`, `main` has nothing missing from `develop` | ⚠️ <what differs>>   _(release PR only; omit on a feature/fix PR)_

### Findings
| # | Severity | Conf. | Location | Finding |
|---|---|---|---|---|
| 1 | 🔴 blocker | high | `path/File.php:42` | <one-line problem> |
| 2 | 🟠 major | medium | `path/Other.php:10` | <one-line problem> |

<details><summary><b>#1: <title></b></summary>

- **Problem:** <execution path → consequence>
- **Evidence:** <what you read/ran; the assumption if confidence < high>
- **Fix:**
  ```php
  // minimal concrete change
  ```
</details>

### Out of scope
- `path/File.php:12-30`: <change not covered by the brief> → <split into own PR / revert>   _(or "none")_

### Open questions (low confidence or rule/code mismatch, not counted)
- <question, or the rule file and code location that disagree>   _(or "none")_

### Checks run
- Reference check: <what was checked at the PR head (`<target>` + this diff)>: <all exist | missing: ...>
- `php -l` on <n> .php files: <ok>; Blade files: not linted
- `pint --test` on <n> files: <clean | n files would change | not run: <reason>>
- Tests: `<exact command>`: <x passed, y failed (pre-existing on base? yes/no/unverified)> | not run: <reason>
- Not verified: <e.g. async queue behavior, React/Flutter impact, production data volume>

<sub>Feedback: answer each finding in this comment thread with `relevant` · `irrelevant` + reason · `fixed` + SHA · `resolved` + reason/link. Do not edit this comment.</sub>
````

Fallbacks, for this template and the §9 re-review header:

- If the PR number or title can't be obtained, use `#<number unknown>` and the commit subject, and say so under *Not verified*. This isn't inventing PR content.
- If a comment link doesn't exist yet, write `<link: not posted yet>`.

If there are no findings, keep the header and verdict ("Approve, no findings"), *Checks run*, and *Not verified*. On a re-review, §9 decides the verdict. "No findings" is not a guarantee. Say what was not checked.

End your reply, after the block, by telling the developer to paste the full review (the verdict and every finding) into the PR's conversation on GitHub (or to confirm you may post it with `gh pr comment`), then answer each finding there using the states in §9.
