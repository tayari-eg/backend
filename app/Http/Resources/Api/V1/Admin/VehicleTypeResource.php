<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A vehicle type as staff edit it: the name in every language.
 *
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
            'name' => collect(Locale::values())
                ->mapWithKeys(fn (string $locale): array => [$locale => $this->getTranslation('name', $locale, false)])
                ->all(),
            'requires_license' => $this->requires_license,
            'can_carry_food' => $this->can_carry_food,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'vehicles_count' => $this->whenCounted('vehicles'),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
