<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Models\Order;
use App\Jobs\SendReceiverCodes;
use Illuminate\Validation\ValidationException;

class ResendReceiverCodesAction
{
    /**
     * Queues new codes for the receiver; the old ones stop working once the new ones are made, and the wrong-code count
     * starts again. Only while the goods are with the tayar.
     *
     * @throws ValidationException (errors.status) when the order is not picked up or ongoing
     */
    public function handle(Order $order): void
    {
        if (! $order->status->hasReceiverCodes()) {
            throw ValidationException::withMessages(['status' => __('orders.codes_not_available')]);
        }

        SendReceiverCodes::dispatch($order->id);
    }
}
