<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderStatusChanged;
use App\Domains\Orders\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmPickupAction
{
    /**
     * The tayar took the items and photographed them: picked_up, with the photos and where the tayar was. The receiver's
     * codes and tracking link then go out by SMS (OrderStatusChanged). The order row is locked first, so a cancel can't
     * cross the pickup.
     *
     * @param  list<UploadedFile>  $photos
     *
     * @throws ValidationException (errors.status) when the order is not this tayar's accepted order
     */
    public function handle(Order $order, User $tayar, array $photos, float $lat, float $lng): Order
    {
        return DB::transaction(function () use ($order, $tayar, $photos, $lat, $lng): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->tayar_id !== $tayar->id || ! $order->status->tayarCanMoveTo(OrderStatus::PickedUp)) {
                throw ValidationException::withMessages(['status' => __('orders.wrong_step')]);
            }

            $from = $order->status;
            $order->moveTo(OrderStatus::PickedUp, ['picked_up_at' => now()], $tayar->id, lat: $lat, lng: $lng);

            foreach ($photos as $photo) {
                $order->addMedia($photo)->toMediaCollection(Order::PICKUP_PHOTOS);
            }

            OrderStatusChanged::dispatch($order, $from, OrderStatus::PickedUp, $tayar);

            return $order;
        });
    }
}
