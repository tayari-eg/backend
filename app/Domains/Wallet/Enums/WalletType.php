<?php

namespace App\Domains\Wallet\Enums;

enum WalletType: string
{
    case Tayar = 'tayar';
    case Business = 'business';
    // Tayari's own wallets, one per SystemWallet case.
    case System = 'system';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
