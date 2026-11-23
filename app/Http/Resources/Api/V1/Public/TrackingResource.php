<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * What the receiver may see from the tracking link: no phones, no codes, no amounts. The tayar's position only while
 * the goods are on the way (picked up or ongoing).
 *
 * @mixin Order
 */
class TrackingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $onTheWay = $this->status->hasReceiverCodes();
        $profile = $this->tayar?->tayarProfile;

        return [
            'code' => $this->code,
            'status' => $this->status,
            'sender_name' => $this->sender->businessProfile?->business_name ?? $this->sender->name,
            'dropoff_address' => $this->dropoff_address,
            'tayar' => $this->tayar === null ? null : [
                'first_name' => Str::before(trim($this->tayar->name), ' '),
                'vehicle' => $this->workSession === null ? null : [
                    'type' => $this->workSession->vehicle->vehicleType->getTranslation('name', app()->getLocale()),
                    'color' => $this->workSession->vehicle->color,
                    'plate_number' => $this->workSession->vehicle->plate_number,
                ],
            ],
            'location' => $onTheWay && $profile?->current_lat !== null ? [
                'lat' => $profile->current_lat,
                'lng' => $profile->current_lng,
                'updated_at' => $profile->location_updated_at?->toIso8601ZuluString(),
            ] : null,
            'can_resend_codes' => $onTheWay,
            'codes_sent_at' => $this->codes_sent_at?->toIso8601ZuluString(),
            'picked_up_at' => $this->picked_up_at?->toIso8601ZuluString(),
            'receiver_confirmed_at' => $this->receiver_confirmed_at?->toIso8601ZuluString(),
            'delivered_at' => $this->delivered_at?->toIso8601ZuluString(),
            'not_delivered_at' => $this->not_delivered_at?->toIso8601ZuluString(),
            'cancelled_at' => $this->cancelled_at?->toIso8601ZuluString(),
            'is_finished' => in_array($this->status, OrderStatus::pastCases(), true),
        ];
    }
}
