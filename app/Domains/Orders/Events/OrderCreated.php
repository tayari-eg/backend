<?php

namespace App\Domains\Orders\Events;

use App\Domains\Orders\Models\Order;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new order is looking for a tayar. Not fired when an idempotency key returns an existing order.
 */
class OrderCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
