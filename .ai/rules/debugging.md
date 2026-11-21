---
paths:
  - "**"
---

# Debugging

## Confirm the root cause before editing

Investigate until the root cause is confirmed, then propose or make a change. Don't start editing code mid-investigation. State the hypothesis and the evidence behind it (a log line, a failing test, a query result, the exact code path) before acting. If the evidence is inconclusive, say so and ask rather than guess.

## Rule out simple causes first

Before suspecting a code bug, rule out environment causes: a stopped service (MySQL, Redis, or a queue worker), a missing or wrong `.env`/config value, pending migrations, schema drift between the `main` and `develop` databases, a stale cache, or old refs. Say which ones you checked.
