---
paths:
  - app/**
  - routes/**
  - config/**
  - resources/views/**
---

# env() Only in Config Files

## Read environment values through config()

Call `env()` only inside `config/*.php`. Everywhere else, read the value with `config('file.key')`, adding the key to the right config file if it doesn't exist. Once config is cached (`php artisan config:cache`), Laravel stops loading `.env`, so `env()` outside config returns `null` in production.
