<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderStatusChanged;
use App\Domains\Orders\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmReceiverAction
{
    /**
     * The tayar called the receiver, who is there today: ongoing, on the way to the drop-off.
     *
     * @throws ValidationException (errors.status) when the order is not this tayar's picked-up order
     */
    public function handle(Order $order, User $tayar, float $lat, float $lng): Order
    {
        return DB::transaction(function () use ($order, $tayar, $lat, $lng): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->tayar_id !== $tayar->id || ! $order->status->tayarCanMoveTo(OrderStatus::Ongoing)) {
                throw ValidationException::withMessages(['status' => __('orders.wrong_step')]);
            }

            $from = $order->status;
            $order->moveTo(OrderStatus::Ongoing, ['receiver_confirmed_at' => now()], $tayar->id, lat: $lat, lng: $lng);

            OrderStatusChanged::dispatch($order, $from, OrderStatus::Ongoing, $tayar);

            return $order;
        });
    }
}
