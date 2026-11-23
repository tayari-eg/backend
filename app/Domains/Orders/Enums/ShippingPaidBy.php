<?php

namespace App\Domains\Orders\Enums;

enum ShippingPaidBy: string
{
    case Sender = 'sender';
    case Receiver = 'receiver';
}
