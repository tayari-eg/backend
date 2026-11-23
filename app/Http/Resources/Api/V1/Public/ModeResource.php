<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Domains\Catalog\Models\Mode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A mode for the tayar's mode picker, with what Go online needs for it.
 *
 * @mixin Mode
 */
class ModeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code->value,
            'name' => $this->getTranslation('name', app()->getLocale()),
            'description' => $this->getTranslation('description', app()->getLocale()),
            'allows_food' => $this->allows_food,
            'requires_area' => $this->code->requiresArea(),
            'requires_radius' => $this->code->requiresRadius(),
            'requires_destination' => $this->code->requiresDestination(),
            'radius_km' => $this->code->requiresRadius() ? [
                'min' => (int) config('tayari.tayars.zone_min_radius_km'),
                'max' => (int) config('tayari.tayars.zone_max_radius_km'),
            ] : null,
        ];
    }
}
