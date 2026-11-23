<?php

namespace App\Domains\Orders\Listeners;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderStatusChanged;
use App\Jobs\SendReceiverCodes;

class SendReceiverCodesOnPickup
{
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->to === OrderStatus::PickedUp) {
            SendReceiverCodes::dispatch($event->order->id);
        }
    }
}
