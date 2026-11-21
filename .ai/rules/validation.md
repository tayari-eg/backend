---
paths:
  - app/Http/Requests/**
  - app/Http/Controllers/**
  - app/Domains/**/Actions/**
---

# Validation

## Validate in a FormRequest under app/Http/Requests/Api/V1/{Public,Auth,Client,Tayar,Admin}

Every input an endpoint uses is validated in a FormRequest under the folder for the client that calls it:

| Folder | Use for |
| --- | --- |
| `app/Http/Requests/Api/V1/Public/` | unauthenticated endpoints (`routes/api/v1/public.php`, e.g. list query parameters) |
| `app/Http/Requests/Api/V1/Auth/` | OTP, login and logout endpoints (`auth/` routes) |
| `app/Http/Requests/Api/V1/Client/` | customer and business endpoints (`client/` routes) |
| `app/Http/Requests/Api/V1/Tayar/` | rider endpoints (`tayar/` routes) |
| `app/Http/Requests/Api/V1/Admin/` | admin endpoints (`admin/` routes) |

Group by domain inside each folder when it grows (e.g. `Api/V1/Client/Orders/StoreOrderRequest.php`). Create them with `php artisan make:request` (`generators.md`).

`authorize()` checks what the route group's middleware doesn't already guarantee: ownership (a sender's own order, an offer meant for this Tayar) or a Spatie permission for admin actions. Return `true` only when the route's middleware and Policy already cover it.

Don't validate inline in controllers or actions (`$request->validate()`, `Validator::make()`). Pass `$request->validated()` (or a DTO built from it) to the Action, never `$request->all()`. Validate money as a bounded decimal (e.g. `decimal:0,2`, `min:`, `max:`), never cast it to float.
