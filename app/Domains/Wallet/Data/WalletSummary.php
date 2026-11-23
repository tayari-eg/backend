<?php

namespace App\Domains\Wallet\Data;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use Brick\Math\BigDecimal;

/**
 * What a wallet's owner can see and staff can pay out. available: the balance without the amounts still waiting for
 * their payout hour. lockedBonus: the signup bonus until the tayar has enough delivered orders. payable: what a payout
 * may take now. cashToHandIn: what a tayar owes Tayari in collected cash, net of earnings.
 */
final readonly class WalletSummary
{
    public function __construct(
        public BigDecimal $balance,
        public BigDecimal $available,
        public BigDecimal $lockedBonus,
        public BigDecimal $payable,
        public BigDecimal $cashToHandIn,
        public bool $blockedFromOffers,
    ) {}

    public static function of(Wallet $wallet): self
    {
        $balance = BigDecimal::of((string) $wallet->balance);
        $pending = self::sum($wallet, fn ($transactions) => $transactions->where('available_at', '>', now()));
        $available = $balance->minus($pending);
        $zero = BigDecimal::zero()->toScale(2);

        if ($wallet->type !== WalletType::Tayar) {
            return new self($balance, $available, $zero, BigDecimal::max($zero, $available), $zero, false);
        }

        $lockedBonus = self::lockedBonus($wallet);
        // A bonus still waiting for its payout hour is already left out of available.
        $pendingBonus = self::sum($wallet, fn ($transactions) => $transactions->where('type', TransactionType::Bonus)->where('available_at', '>', now()));
        $payable = $available->minus(BigDecimal::max($zero, $lockedBonus->minus($pendingBonus)));

        return new self(
            $balance,
            $available,
            $lockedBonus,
            BigDecimal::max($zero, $payable),
            BigDecimal::max($zero, $balance->negated()),
            self::owesTooMuchCash($balance),
        );
    }

    public static function owesTooMuchCash(BigDecimal $balance): bool
    {
        return $balance->isLessThan(BigDecimal::of(config('tayari.wallet.max_cash_owed'))->negated());
    }

    private static function lockedBonus(Wallet $wallet): BigDecimal
    {
        $bonus = self::sum($wallet, fn ($transactions) => $transactions->where('type', TransactionType::Bonus));

        if (! $bonus->isPositive()) {
            return BigDecimal::zero()->toScale(2);
        }

        $delivered = Order::query()->where('tayar_id', $wallet->user_id)->where('status', OrderStatus::Delivered)->count();

        return $delivered < config()->integer('tayari.wallet.bonus_unlock_delivered_orders') ? $bonus : BigDecimal::zero()->toScale(2);
    }

    private static function sum(Wallet $wallet, callable $scope): BigDecimal
    {
        $sum = $wallet->transactions()->tap($scope)->sum('amount');

        return BigDecimal::of((string) ($sum ?: '0'))->toScale(2);
    }
}
