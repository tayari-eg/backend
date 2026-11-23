<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Domains\Catalog\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Area
 */
class AreaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale()),
            'city' => $this->getTranslation('city', app()->getLocale()),
            'center' => ['lat' => (float) $this->center_lat, 'lng' => (float) $this->center_lng],
            'radius_km' => (float) $this->radius_km,
        ];
    }
}
