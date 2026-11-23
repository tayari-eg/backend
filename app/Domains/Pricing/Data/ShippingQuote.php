<?php

namespace App\Domains\Pricing\Data;

use App\Domains\Catalog\Models\Mode;
use Brick\Math\BigDecimal;

/**
 * The priced trip: the mode used and every part of the fee, in EGP.
 */
final readonly class ShippingQuote
{
    public function __construct(
        public Mode $mode,
        public BigDecimal $distanceKm,
        public BigDecimal $baseFare,
        public BigDecimal $pricePerKm,
        public BigDecimal $distanceFare,
        public BigDecimal $minimumFare,
        public BigDecimal $foodExtra,
        public BigDecimal $shippingFee,
        public BigDecimal $tayarSharePercent,
        public BigDecimal $tayarEarning,
        public BigDecimal $platformFee,
    ) {}

    /**
     * The order columns, as decimal strings.
     *
     * @return array<string, int|string>
     */
    public function toOrderAttributes(): array
    {
        return [
            'mode_id' => $this->mode->id,
            'distance_km' => (string) $this->distanceKm,
            'base_fare' => (string) $this->baseFare,
            'price_per_km' => (string) $this->pricePerKm,
            'distance_fare' => (string) $this->distanceFare,
            'minimum_fare' => (string) $this->minimumFare,
            'food_extra' => (string) $this->foodExtra,
            'shipping_fee' => (string) $this->shippingFee,
            'tayar_share_percent' => (string) $this->tayarSharePercent,
            'tayar_earning' => (string) $this->tayarEarning,
            'platform_fee' => (string) $this->platformFee,
        ];
    }
}
