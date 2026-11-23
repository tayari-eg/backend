<?php

namespace App\Domains\Pricing\Models;

use App\Domains\Catalog\Models\Mode;
use App\Domains\Tayars\Models\VehicleType;
use Database\Factories\PricingRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How the shipping fee is built for a mode, for one vehicle type or (vehicle_type_id null) all of them.
 */
#[Fillable(['mode_id', 'vehicle_type_id', 'base_fare', 'price_per_km', 'minimum_fare', 'food_extra', 'tayar_share_percent', 'is_active'])]
#[UseFactory(PricingRuleFactory::class)]
class PricingRule extends Model
{
    /** @use HasFactory<PricingRuleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode_id' => 'integer',
            'vehicle_type_id' => 'integer',
            'base_fare' => 'decimal:2',
            'price_per_km' => 'decimal:2',
            'minimum_fare' => 'decimal:2',
            'food_extra' => 'decimal:2',
            'tayar_share_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Mode, $this>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(Mode::class);
    }

    /**
     * @return BelongsTo<VehicleType, $this>
     */
    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }
}
