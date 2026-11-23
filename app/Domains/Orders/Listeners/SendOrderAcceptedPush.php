<?php

namespace App\Domains\Orders\Listeners;

use App\Domains\Accounts\Actions\SendPushNotificationAction;
use App\Domains\Orders\Enums\OrderPushType;
use App\Domains\Orders\Events\OrderAccepted;

class SendOrderAcceptedPush
{
    public function __construct(private SendPushNotificationAction $sendPush) {}

    public function handle(OrderAccepted $event): void
    {
        $order = $event->order->loadMissing(['sender', 'tayar']);
        $type = OrderPushType::OrderAccepted;

        $this->sendPush->handle(
            $order->sender,
            $type->value,
            $type->titleKey(),
            $type->bodyKey(),
            ['tayar' => $order->tayar->name, 'code' => $order->code],
            ['order_id' => $order->id],
        );
    }
}
