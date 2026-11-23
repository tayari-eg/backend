<?php

namespace App\Domains\Orders\Events;

use App\Domains\Orders\Enums\OrderCanceller;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order, public OrderStatus $fromStatus, public OrderCanceller $canceller) {}
}
