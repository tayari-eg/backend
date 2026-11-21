---
paths:
  - app/**
  - database/**
  - routes/**
  - tests/**
---

# No Made-Up SQL

## Verify columns before writing a query

Never write a query, raw or Eloquent, against table or column names you haven't verified. Check them against the migrations in `database/migrations/` (the latest migration touching the table wins), the model's `$fillable`/`$casts`, or a sample row. If you can't verify the schema, ask for the table definition or an existing query to adapt.

## Check the branch you're on

Feature branches are cut from and merged into `main` until a `develop` branch exists for staging. Once it does, `develop` can be ahead of `main` (migrations not yet released). Verify columns against the migrations on the branch you're changing, not another branch or a local database migrated from a different branch.
