<?php

namespace App\Domains\Pricing\Actions;

use App\Domains\Orders\Models\Order;
use App\Domains\Pricing\Data\TayarEarning;
use App\Domains\Pricing\Models\PricingRule;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class CalculateTayarEarningAction
{
    private const int MONEY_SCALE = 2;

    /**
     * The tayar's share of the order's shipping fee for this vehicle type: the order's mode rule for the vehicle type
     * when an active one exists, else its active "all vehicles" rule, else the share the order was priced with. The fee
     * itself never changes. tayar_earning = fee × share ÷ 100, rounded half up; platform_fee = fee − tayar_earning.
     */
    public function handle(Order $order, int $vehicleTypeId): TayarEarning
    {
        $rule = PricingRule::query()
            ->where('mode_id', $order->mode_id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('vehicle_type_id', $vehicleTypeId)->orWhereNull('vehicle_type_id'))
            ->get(['vehicle_type_id', 'tayar_share_percent'])
            ->sortBy(fn (PricingRule $rule): int => $rule->vehicle_type_id === null ? 1 : 0)
            ->first();

        $sharePercent = BigDecimal::of($rule?->tayar_share_percent ?? $order->tayar_share_percent);
        $shippingFee = BigDecimal::of($order->shipping_fee);
        $tayarEarning = $shippingFee->multipliedBy($sharePercent)->dividedBy(100, self::MONEY_SCALE, RoundingMode::HalfUp);

        return new TayarEarning(
            tayarSharePercent: $sharePercent->toScale(self::MONEY_SCALE),
            tayarEarning: $tayarEarning,
            platformFee: $shippingFee->minus($tayarEarning)->toScale(self::MONEY_SCALE),
        );
    }
}
