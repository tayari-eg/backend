---
paths:
  - app/**
  - database/**
  - routes/**
  - tests/**
---

# Tayari Domain Rules

Business rules the code must never break. If a task seems to need breaking one, stop and ask.

## Money

- **Ledger first.** Every wallet change is a row in `wallet_transactions`, written inside a DB transaction together with the cached `wallets.balance` update. Never change a balance without its ledger row, and never write the two outside one transaction. Lock the wallet row (`lockForUpdate()`) when the balance decides the outcome.
- **Never pay twice.** Unique indexes stop paying the same order or bonus twice (e.g. one earning row per order per wallet, one bonus row per campaign and order or target). Retries and re-run jobs must be safe.
- **No float.** Money is stored as `decimal(10,2)`, cast to `decimal:2`, and calculated with brick/math (`BigDecimal`, with an explicit scale and `RoundingMode` when dividing). Never use `float`, `+`/`-`/`*` on amounts, or `round()`.
- **Receivers pay before delivery**, and Tayari holds the money until the order ends.
- **On delivered**, the goods money goes to the business wallet and the Tayar's share to the Tayar wallet, in one transaction.
- **On not delivered, or cancelled after payment**, the money is refunded.
- **Withdrawable from 11 pm.** Tayar earnings and bonuses become withdrawable from 11 pm business time the same day, through `available_at`.

## Order statuses

- Allowed transitions only: `looking_for_tayar → pending → picked_up → ongoing → delivered | not_delivered`. `cancelled` is allowed only before pickup.
- The status enum owns the transitions (`OrderStatus::canTransitionTo()`). Don't check transitions ad hoc.
- Every status change writes a row to `order_status_histories`, in the same transaction as the status update.

## Codes and OTPs

- Delivery and refusal codes and OTPs are stored only as hashes, with an expiry and an attempt limit. Never log them or return them, except to the intended recipient.
- OTP and code endpoints are rate limited.

## Incentives

- Tayar ranks are recalculated nightly. Moving down a rank waits 7 days.
- Bonuses come from `bonus_campaigns` (`per_order` or `target`) or from manual grants, and are always written as a `bonus` wallet transaction.
- Campaign start and end are evaluated in business time.

## Time

- Store every timestamp in UTC (`config('app.timezone')` stays `UTC`).
- Business rules (the 11 pm withdrawal cut-off, campaign start/end, "today", nightly jobs) are calculated in `config('app.business_timezone')` (`Africa/Cairo` by default, from `BUSINESS_TIMEZONE`), then converted to UTC to store or compare. Example: `now(config('app.business_timezone'))->setTime(23, 0)->utc()`.
- Never hard-code `'Africa/Cairo'` in business code; read the config key.

## Files

- Media defaults to the private `local` disk; only files that are meant to be public (for example category icons) use `->useDisk('public')` explicitly.
