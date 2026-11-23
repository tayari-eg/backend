<?php

namespace App\Domains\Accounts\Enums;

enum PushDeliveryResult: string
{
    case Sent = 'sent';
    case TokenRemoved = 'token_removed';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'sent',
            self::TokenRemoved => 'dead token removed',
            self::Dropped => 'dropped, the device changed owner or token',
        };
    }
}
