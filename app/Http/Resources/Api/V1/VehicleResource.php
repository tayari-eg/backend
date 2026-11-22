<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Tayars\Enums\VehicleDocument;
use App\Domains\Tayars\Models\Vehicle;
use App\Http\Resources\Api\V1\Public\VehicleTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vehicle
 */
class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_type' => VehicleTypeResource::make($this->whenLoaded('vehicleType')),
            'plate_number' => $this->plate_number,
            'model' => $this->model,
            'color' => $this->color,
            'year' => $this->year,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'documents' => $this->whenLoaded('media', fn (): array => [
                VehicleDocument::VehicleLicense->value => MediaResource::optional($this->getFirstMedia(VehicleDocument::VehicleLicense->value)),
                VehicleDocument::VehiclePhotos->value => MediaResource::collection($this->getMedia(VehicleDocument::VehiclePhotos->value)),
            ]),
        ];
    }
}
