<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Models\VehicleType;

class CreateVehicleTypeAction
{
    /**
     * A type without a sort order goes to the end of the list.
     *
     * @param  array{ar: string, en: string}  $name
     */
    public function handle(array $name, bool $requiresLicense, bool $canCarryFood, bool $isActive, ?int $sortOrder): VehicleType
    {
        return VehicleType::query()->create([
            'name' => $name,
            'requires_license' => $requiresLicense,
            'can_carry_food' => $canCarryFood,
            'is_active' => $isActive,
            'sort_order' => $sortOrder ?? (int) VehicleType::query()->max('sort_order') + 1,
        ]);
    }
}
