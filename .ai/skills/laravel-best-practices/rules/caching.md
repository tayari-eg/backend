# Caching Best Practices

## Use `Cache::remember()` for Cache-Aside Reads

`Cache::remember()` implements a cache-aside read without a separate truthiness check. It does not prevent concurrent requests from computing the same missing value; use an atomic lock when duplicate computation must be prevented.

The manual version below incorrectly treats valid falsy values, such as `false` or `0`, as cache misses.

Incorrect:

```php
$val = Cache::get('stats');
if (! $val) {
    $val = $this->computeStats();
    Cache::put('stats', $val, 60);
}
```

Correct:

```php
$val = Cache::remember('stats', 60, fn () => $this->computeStats());
```

## Consider `Cache::flexible()` for Stale-While-Revalidate

For frequently read keys, `Cache::flexible()` can serve stale data during a defined stale period and register a deferred refresh. During an HTTP request, that refresh normally runs after the response; it is not a durable background job. Once the stale period has elapsed, the request recomputes the value synchronously.

Synchronous expiration:

```php
Cache::remember('users', 300, fn () => User::all());
```

Stale-while-revalidate tradeoff:

```php
Cache::flexible('users', [300, 600], fn () => User::all());
```

This value is fresh for five minutes and may be served stale until ten minutes after it was cached.

## Use Cache Tags to Invalidate Related Groups

Tags group related entries for invalidation without tracking each key. Cache tags are not supported by the `file`, `dynamodb`, or `database` drivers; confirm support before choosing a store.

```php
Cache::tags(['user-1'])->flush();
```

Flush the narrowest tag that covers the change. Flushing a broad tag on a write path empties every entry under that tag.

## Use `Cache::add()` for Atomic Conditional Writes

`add()` atomically writes a value only when the key does not already exist.

Incorrect:

```php
if (! Cache::has('lock')) {
    Cache::put('lock', true, 10);
}
```

Correct:

```php
Cache::add('lock', true, 10);
```

Use `Cache::lock()` rather than an ordinary cache key when lock ownership and safe release are required.

## Use `once()` for In-Process Memoization

`once()` memoizes a callback's return value for the current request or job. Calls made from an object instance are scoped to that instance. `once()` does not itself read from an external cache store.

```php
public function roles(): Collection
{
    return once(fn () => $this->loadRoles());
}
```

Repeated calls return the memoized result without rerunning the callback. Use `once()` for repeated computation within one execution.

To avoid repeated store lookups for the same key within one execution, use the `memo` cache driver, which keeps resolved values in memory for the current request or job:

```php
$settings = Cache::memo()->remember('settings', 300, fn () => $this->loadSettings());
```
