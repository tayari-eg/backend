<?php

namespace App\Domains\Wallet\Enums;

enum WalletPushType: string
{
    case PayoutRecorded = 'wallet_payout_recorded';
    case CashReceived = 'wallet_cash_received';
    case Adjusted = 'wallet_adjusted';
    case SignupBonus = 'wallet_signup_bonus';

    /**
     * The push for a posting the wallet's owner hears about; delivered orders and cancel fees already have order pushes.
     */
    public static function forPosting(PostingType $type): ?self
    {
        return match ($type) {
            PostingType::Payout => self::PayoutRecorded,
            PostingType::CashHandedIn => self::CashReceived,
            PostingType::Adjustment, PostingType::Reversal => self::Adjusted,
            PostingType::SignupBonus => self::SignupBonus,
            default => null,
        };
    }

    public function titleKey(): string
    {
        return "push.{$this->value}.title";
    }

    public function bodyKey(): string
    {
        return "push.{$this->value}.body";
    }
}
