<?php

namespace App\Domains\Orders\Enums;

enum OrderReasonType: string
{
    case Cancel = 'cancel';
    case NotDelivered = 'not_delivered';
}
