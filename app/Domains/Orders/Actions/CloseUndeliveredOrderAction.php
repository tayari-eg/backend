<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderStatusChanged;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseUndeliveredOrderAction
{
    /**
     * Staff close a picked-up or ongoing order as not_delivered without a code, when the receiver can't be reached, so
     * the tayar is never stuck holding it. The only status staff can force. The history row names the staff member and
     * has no position. The sender and the tayar are both told.
     *
     * @throws ValidationException (errors.status) when the order is not picked up or ongoing
     */
    public function handle(Order $order, User $staff, OrderReason $reason, string $note): Order
    {
        return DB::transaction(function () use ($order, $staff, $reason, $note): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->status->staffCanCloseAsNotDelivered()) {
                throw ValidationException::withMessages(['status' => __('orders.cannot_close_not_delivered')]);
            }

            $from = $order->status;
            $order->moveTo(OrderStatus::NotDelivered, [
                'not_delivered_at' => now(),
                'not_delivered_reason_id' => $reason->id,
                'not_delivered_note' => $note,
                'delivery_code_hash' => null,
                'refusal_code_hash' => null,
            ], $staff->id, $note);

            OrderStatusChanged::dispatch($order, $from, OrderStatus::NotDelivered, $staff);

            return $order;
        });
    }
}
