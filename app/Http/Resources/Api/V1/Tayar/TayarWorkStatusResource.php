<?php

namespace App\Http\Resources\Api\V1\Tayar;

use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
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
            'work_session' => $this->currentWorkSession === null ? null : self::workSession($this->currentWorkSession),
            'location' => $this->current_lat === null ? null : [
                'lat' => (float) $this->current_lat,
                'lng' => (float) $this->current_lng,
                'updated_at' => $this->location_updated_at?->toIso8601ZuluString(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function workSession(TayarWorkSession $session): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $session->id,
            'vehicle_id' => $session->vehicle_id,
            'mode' => $session->mode === null ? null : [
                'id' => $session->mode->id,
                'code' => $session->mode->code->value,
                'name' => $session->mode->getTranslation('name', $locale),
            ],
            'area' => $session->area === null ? null : [
                'id' => $session->area->id,
                'name' => $session->area->getTranslation('name', $locale),
            ],
            'radius_km' => $session->radius_km === null ? null : (float) $session->radius_km,
            'destination' => $session->destination_address === null ? null : [
                'address' => $session->destination_address,
                'lat' => (float) $session->destination_lat,
                'lng' => (float) $session->destination_lng,
            ],
            'started_at' => $session->started_at->toIso8601ZuluString(),
        ];
    }
}
