<?php

namespace App\Domains\Orders\Enums;

/**
 * The data.type of the order pushes. The titles and bodies are under push.<type> in lang/{ar,en}/push.php.
 */
enum OrderPushType: string
{
    case OrderOffer = 'order_offer';
    case OrderAccepted = 'order_accepted';
    case OrderNoTayar = 'order_no_tayar';
    case OrderCancelled = 'order_cancelled';
    case OrderPickedUp = 'order_picked_up';
    case OrderOnTheWay = 'order_on_the_way';
    case OrderDelivered = 'order_delivered';
    case OrderNotDelivered = 'order_not_delivered';

    public function titleKey(): string
    {
        return "push.{$this->value}.title";
    }

    public function bodyKey(): string
    {
        return "push.{$this->value}.body";
    }
}
