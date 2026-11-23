<?php

namespace App\Http\Resources\Api\V1\Tayar;

use App\Domains\Orders\Models\OrderOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An offer as the tayar sees it: the trip and the earning, but no phones until the tayar accepts.
 *
 * @mixin OrderOffer
 */
class OfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->effectiveStatus(),
            'round' => $this->round,
            'distance_km' => $this->distance_km,
            'tayar_earning' => $this->tayar_earning,
            'expires_at' => $this->expires_at->toIso8601ZuluString(),
            // Counted on the server, so a wrong phone clock can't shorten or stretch the countdown.
            'expires_in_seconds' => max(0, (int) now()->diffInSeconds($this->expires_at, false)),
            'order' => $this->whenLoaded('order', fn (): array => [
                'id' => $this->order->id,
                'code' => $this->order->code,
                'mode' => [
                    'code' => $this->order->mode->code->value,
                    'name' => $this->order->mode->getTranslation('name', app()->getLocale()),
                ],
                'category' => [
                    'id' => $this->order->category->id,
                    'name' => $this->order->category->getTranslation('name', app()->getLocale()),
                    'is_food' => $this->order->category->is_food,
                ],
                'details' => $this->order->details,
                'pickup' => [
                    'address' => $this->order->pickup_address,
                    'lat' => $this->order->pickup_lat,
                    'lng' => $this->order->pickup_lng,
                ],
                'dropoff' => [
                    'address' => $this->order->dropoff_address,
                    'lat' => $this->order->dropoff_lat,
                    'lng' => $this->order->dropoff_lng,
                ],
                'distance_km' => $this->order->distance_km,
            ]),
        ];
    }
}
