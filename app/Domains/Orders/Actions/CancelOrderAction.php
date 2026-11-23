<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrderAction
{
    /**
     * Cancels an order that no tayar has accepted yet, by its sender or by staff, with a cancel reason and an optional
     * note (kept on the status history row). The row is locked so a cancel can't cross a later accept.
     *
     * @throws ValidationException (errors.status) when the order can no longer be cancelled
     */
    public function handle(Order $order, User $actor, OrderReason $reason, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $actor, $reason, $note): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->canBeCancelled()) {
                throw ValidationException::withMessages(['status' => __('orders.cannot_cancel')]);
            }

            $from = $order->status;

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancel_reason_id' => $reason->id,
            ])->save();

            $order->statusHistories()->create([
                'from_status' => $from,
                'to_status' => OrderStatus::Cancelled,
                'changed_by' => $actor->id,
                'note' => $note,
            ]);

            return $order;
        });
    }
}
