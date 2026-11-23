<?php

namespace App\Domains\Pricing\Actions;

use App\Domains\Catalog\Models\Mode;
use App\Domains\Pricing\Models\PricingRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePricingRuleAction
{
    /**
     * One rule per mode and vehicle type (or all vehicles, active or not). Amounts arrive as decimal strings.
     *
     * @param  array{mode_id: int, vehicle_type_id?: int|null, base_fare: string, price_per_km: string, minimum_fare: string, food_extra?: string|null, tayar_share_percent: string, is_active?: bool}  $attributes
     *
     * @throws ValidationException when the mode already has a rule for this vehicle type
     */
    public function handle(array $attributes): PricingRule
    {
        return DB::transaction(function () use ($attributes): PricingRule {
            $vehicleTypeId = $attributes['vehicle_type_id'] ?? null;

            // The unique index lets several "all vehicles" rows through, so rules for one mode are written one at a time.
            Mode::query()->lockForUpdate()->findOrFail($attributes['mode_id']);

            if (PricingRule::query()->where('mode_id', $attributes['mode_id'])->where('vehicle_type_id', $vehicleTypeId)->exists()) {
                throw ValidationException::withMessages(['vehicle_type_id' => __('admin.pricing_rule_exists')]);
            }

            return PricingRule::query()->create(['is_active' => true, ...$attributes, 'vehicle_type_id' => $vehicleTypeId]);
        });
    }
}
