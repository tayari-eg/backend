<?php

namespace App\Domains\Pricing\Actions;

use App\Domains\Catalog\Models\Mode;
use App\Domains\Pricing\Models\PricingRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePricingRuleAction
{
    /**
     * @param  array{mode_id?: int, vehicle_type_id?: int|null, base_fare?: string, price_per_km?: string, minimum_fare?: string, food_extra?: string|null, tayar_share_percent?: string, is_active?: bool}  $attributes
     *
     * @throws ValidationException when the new mode and vehicle type already have another rule
     */
    public function handle(PricingRule $pricingRule, array $attributes): PricingRule
    {
        DB::transaction(function () use ($pricingRule, $attributes): void {
            // Rule first, then mode: CreatePricingRuleAction only locks the mode, so the two can't deadlock.
            $fresh = PricingRule::query()->lockForUpdate()->findOrFail($pricingRule->id);
            $modeId = Arr::get($attributes, 'mode_id', $fresh->mode_id);
            $vehicleTypeId = Arr::get($attributes, 'vehicle_type_id', $fresh->vehicle_type_id);

            Mode::query()->lockForUpdate()->findOrFail($modeId);

            $isTaken = PricingRule::query()
                ->whereKeyNot($fresh->id)
                ->where('mode_id', $modeId)
                ->where('vehicle_type_id', $vehicleTypeId)
                ->exists();

            if ($isTaken) {
                throw ValidationException::withMessages(['vehicle_type_id' => __('admin.pricing_rule_exists')]);
            }

            $fresh->update($attributes);
        });

        return $pricingRule->refresh();
    }
}
