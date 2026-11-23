<?php

namespace App\Domains\Wallet\Enums;

enum SettlementKind: string
{
    // Tayari paid the wallet's owner (a tayar or a business).
    case Payout = 'payout';
    // A tayar handed in collected cash.
    case CashHandedIn = 'cash_handed_in';

    public function postingType(): PostingType
    {
        return match ($this) {
            self::Payout => PostingType::Payout,
            self::CashHandedIn => PostingType::CashHandedIn,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
