<?php

namespace App\Domains\Wallet\Enums;

enum TransactionType: string
{
    // The goods value Tayari owes the business for a delivered order.
    case GoodsPayout = 'goods_payout';
    // The shipping fee the business pays at delivery when it pays the shipping.
    case ShippingFee = 'shipping_fee';
    case DeliveryEarning = 'delivery_earning';
    // The cash the tayar collected from the receiver and owes Tayari.
    case CashCollected = 'cash_collected';
    case Commission = 'commission';
    case CancelFeeEarned = 'cancel_fee_earned';
    case CancelFeeCharged = 'cancel_fee_charged';
    case Bonus = 'bonus';
    case CashHandedIn = 'cash_handed_in';
    case Payout = 'payout';
    case Adjustment = 'adjustment';

    /**
     * Tayar earnings and bonuses wait for the day's payout hour; everything else counts at once.
     */
    public function waitsForPayoutHour(): bool
    {
        return in_array($this, [self::DeliveryEarning, self::CancelFeeEarned, self::Bonus], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
