<?php

namespace App\Http\Resources\Api\V1\Tayar;

use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The home toggle state: online or not, the current work session and the last position.
 *
 * @mixin TayarProfile
 */
class TayarWorkStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'is_online' => $this->is_online,
            'work_session' => $this->currentWorkSession === null ? null : [
                'id' => $this->currentWorkSession->id,
                'vehicle_id' => $this->currentWorkSession->vehicle_id,
                'started_at' => $this->currentWorkSession->started_at->toIso8601ZuluString(),
            ],
            'location' => $this->current_lat === null ? null : [
                'lat' => (float) $this->current_lat,
                'lng' => (float) $this->current_lng,
                'updated_at' => $this->location_updated_at?->toIso8601ZuluString(),
            ],
        ];
    }
}
