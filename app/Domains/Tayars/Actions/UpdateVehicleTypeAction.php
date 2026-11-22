<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Models\VehicleType;

class UpdateVehicleTypeAction
{
    /**
     * @param  array{name?: array{ar: string, en: string}, requires_license?: bool, can_carry_food?: bool, is_active?: bool, sort_order?: int}  $attributes
     */
    public function handle(VehicleType $vehicleType, array $attributes): VehicleType
    {
        $vehicleType->update($attributes);

        return $vehicleType;
    }
}
