<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Orders\Models\OrderOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One offer of an order. status is as of now: an offer past expires_at reads expired before the sweep writes it.
 *
 * @mixin OrderOffer
 */
class OrderOfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tayar' => $this->whenLoaded('tayar', fn (): array => [
                'id' => $this->tayar->id,
                'name' => $this->tayar->name,
                'phone' => $this->tayar->phone,
            ]),
            'work_session_id' => $this->work_session_id,
            'round' => $this->round,
            'distance_km' => $this->distance_km,
            'tayar_share_percent' => $this->tayar_share_percent,
            'tayar_earning' => $this->tayar_earning,
            'platform_fee' => $this->platform_fee,
            'status' => $this->effectiveStatus(),
            'expires_at' => $this->expires_at->toIso8601ZuluString(),
            'responded_at' => $this->responded_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
