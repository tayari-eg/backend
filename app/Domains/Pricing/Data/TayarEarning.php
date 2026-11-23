<?php

namespace App\Domains\Pricing\Data;

use Brick\Math\BigDecimal;

/**
 * How one shipping fee splits between a tayar and Tayari. Amounts have two decimals.
 */
final readonly class TayarEarning
{
    public function __construct(
        public BigDecimal $tayarSharePercent,
        public BigDecimal $tayarEarning,
        public BigDecimal $platformFee,
    ) {}

    /**
     * @return array{tayar_share_percent: string, tayar_earning: string, platform_fee: string}
     */
    public function toAttributes(): array
    {
        return [
            'tayar_share_percent' => (string) $this->tayarSharePercent,
            'tayar_earning' => (string) $this->tayarEarning,
            'platform_fee' => (string) $this->platformFee,
        ];
    }
}
