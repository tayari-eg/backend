<?php

namespace App\Domains\Orders\Listeners;

use App\Domains\Accounts\Actions\SendPushNotificationAction;
use App\Domains\Orders\Enums\OrderCanceller;
use App\Domains\Orders\Enums\OrderPushType;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderCancelled;
use App\Models\User;

class SendOrderCancelledPushes
{
    public function __construct(private SendPushNotificationAction $sendPush) {}

    /**
     * The tayar of an accepted order learns it was cancelled, and by whom; the sender learns when no tayar accepted in
     * time.
     */
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order->loadMissing(['sender', 'tayar']);

        if ($event->fromStatus === OrderStatus::Pending && $order->tayar !== null) {
            $this->send($order->tayar, OrderPushType::OrderCancelled, $order->code, ['order_id' => $order->id, 'cancelled_by_type' => $event->canceller->value]);
        }

        if ($event->canceller === OrderCanceller::System) {
            $this->send($order->sender, OrderPushType::OrderNoTayar, $order->code, ['order_id' => $order->id]);
        }
    }

    /**
     * @param  array<string, string|int>  $data
     */
    private function send(User $user, OrderPushType $type, string $code, array $data): void
    {
        $this->sendPush->handle($user, $type->value, $type->titleKey(), $type->bodyKey(), ['code' => $code], $data);
    }
}
