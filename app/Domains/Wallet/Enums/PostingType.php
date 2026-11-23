<?php

namespace App\Domains\Wallet\Enums;

enum PostingType: string
{
    case OrderDelivered = 'order_delivered';
    case CancelFee = 'cancel_fee';
    case SignupBonus = 'signup_bonus';
    case Payout = 'payout';
    case CashHandedIn = 'cash_handed_in';
    case Adjustment = 'adjustment';
    case Reversal = 'reversal';

    /**
     * Staff can reverse only what staff recorded; order and bonus postings are corrected with an adjustment.
     */
    public function canBeReversed(): bool
    {
        return in_array($this, [self::Payout, self::CashHandedIn, self::Adjustment], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
