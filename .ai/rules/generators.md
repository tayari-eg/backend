---
paths:
  - app/**
  - database/**
  - tests/**
---

# Generators

## Always use php artisan make:*

Create new files with the matching generator, then edit the generated file: `make:migration`, `make:model`, `make:controller`, `make:request`, `make:resource`, `make:policy`, `make:job`, `make:event`, `make:listener`, `make:observer`, `make:mail`, `make:command`, `make:test`, `make:factory`, `make:seeder`, and `make:class` for a plain class. Pass `--no-interaction`. Inside Sail, run `./vendor/bin/sail artisan make:...`.

## Never hand-write a migration

A migration's filename timestamp must come from `php artisan make:migration`. Never hand-write the file or invent a timestamp (`..._000001_`, `..._100000_` and similar round times are a sign of a hand-written file). Never edit a migration that has already shipped: create a new one.
