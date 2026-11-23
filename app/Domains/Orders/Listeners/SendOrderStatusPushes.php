<?php

namespace App\Domains\Orders\Listeners;

use App\Domains\Accounts\Actions\SendPushNotificationAction;
use App\Domains\Orders\Enums\OrderPushType;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderStatusChanged;

class SendOrderStatusPushes
{
    public function __construct(private SendPushNotificationAction $sendPush) {}

    /**
     * The sender hears of every delivery step. The tayar starts every step, so the tayar only hears when staff close
     * the order as not delivered.
     */
    public function handle(OrderStatusChanged $event): void
    {
        $type = match ($event->to) {
            OrderStatus::PickedUp => OrderPushType::OrderPickedUp,
            OrderStatus::Ongoing => OrderPushType::OrderOnTheWay,
            OrderStatus::Delivered => OrderPushType::OrderDelivered,
            OrderStatus::NotDelivered => OrderPushType::OrderNotDelivered,
            default => null,
        };

        if ($type === null) {
            return;
        }

        $order = $event->order->loadMissing(['sender', 'tayar']);
        $send = fn ($user) => $this->sendPush->handle($user, $type->value, $type->titleKey(), $type->bodyKey(), ['code' => $order->code], ['order_id' => $order->id]);

        $send($order->sender);

        if ($type === OrderPushType::OrderNotDelivered && $order->tayar !== null && $event->changedBy?->id !== $order->tayar_id) {
            $send($order->tayar);
        }
    }
}
