<?php

namespace App\Domains\Orders\Enums;

/**
 * Who cancels an order. The system cancels an order no tayar accepted in time.
 */
enum OrderCanceller: string
{
    case Sender = 'sender';
    case Staff = 'staff';
    case System = 'system';
}
