<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Models\VehicleType;

class DeactivateVehicleTypeAction
{
    /**
     * Existing vehicles keep the type; it only disappears from the public list and new applications.
     */
    public function handle(VehicleType $vehicleType): VehicleType
    {
        $vehicleType->forceFill(['is_active' => false])->save();

        return $vehicleType;
    }
}
