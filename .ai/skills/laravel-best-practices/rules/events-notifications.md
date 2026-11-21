# Events and Notifications Best Practices

## Make Sure Listeners and Observers Are Registered

Laravel 13 has no `EventServiceProvider` by default and discovers listeners automatically in `app/Listeners`. Tayari keeps listeners in `app/Domains/*/Listeners` (`.ai/rules/architecture.md`), which default discovery does not scan, so check how the app registers them (`withEvents(discover: [...])` in `bootstrap/app.php`, or `Event::listen()` in a service provider) and follow that. A listener that exists but isn't registered silently never fires. Register observers with the `#[ObservedBy]` attribute or in a service provider, following the existing pattern.

## Use `ShouldDispatchAfterCommit` Inside Transactions

When an event is dispatched inside a database transaction, `ShouldDispatchAfterCommit` delays dispatch until all open database transactions commit. If a transaction rolls back, Laravel discards the event. This affects synchronous and queued listeners; it is not limited to queue timing.

```php
class OrderShipped implements ShouldDispatchAfterCommit {}
```

## Queue Slow Notifications

Queue notifications that call external services, such as email, text messaging, or Slack, when they do not need to complete before the response. Keep a notification synchronous when immediate completion or failure feedback is part of the operation.

```php
class InvoicePaid extends Notification implements ShouldQueue
{
    use Queueable;
}
```

## Dispatch Queued Notifications After Commit

A queued notification sent inside a database transaction can run before the transaction commits. Call `afterCommit()` on the queued notification, or enable the queue connection's `after_commit` option, when its delivery depends on committed data. This setting has no scheduling effect on a synchronous notification.

```php
$user->notify((new InvoicePaid($invoice))->afterCommit());
```

## Route Notification Channels to Dedicated Queues

Different notification channels can have different latency and priority requirements. Implement `viaQueues()` when channels should use separate queues.

## Use On-Demand Notifications for Non-User Recipients

Avoid creating dummy models to send notifications to arbitrary addresses.

```php
Notification::route('mail', 'admin@example.com')->notify(new SystemAlert());
```

## Implement `HasLocalePreference` on Notifiable Models

Implement `HasLocalePreference::preferredLocale()` on a notifiable model when notifications and mailables should use the recipient's locale. Laravel also preserves that locale for queued delivery. An explicit `locale()` call can still override the preference for an individual notification.
