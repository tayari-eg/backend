<?php

namespace App\Domains\Accounts\Enums;

/**
 * What the user picked on the first screen. Individual customers come in a later phase.
 */
enum RegisterAs: string
{
    case Business = 'business';
    case Tayar = 'tayar';

    /**
     * A business needs the business account type, or the business profile form refuses it.
     */
    public function accountType(): AccountType
    {
        return match ($this) {
            self::Business => AccountType::Business,
            self::Tayar => AccountType::Individual,
        };
    }
}
