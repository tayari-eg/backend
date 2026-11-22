<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VehicleType
 */
class VehicleTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale()),
            'requires_license' => $this->requires_license,
            'can_carry_food' => $this->can_carry_food,
        ];
    }
}
