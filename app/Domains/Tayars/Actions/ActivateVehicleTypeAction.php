<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Models\VehicleType;

class ActivateVehicleTypeAction
{
    public function handle(VehicleType $vehicleType): VehicleType
    {
        $vehicleType->forceFill(['is_active' => true])->save();

        return $vehicleType;
    }
}
