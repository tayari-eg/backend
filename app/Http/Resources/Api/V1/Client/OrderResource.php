<?php

namespace App\Http\Resources\Api\V1\Client;

use App\Domains\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An order as its sender sees it. Amounts are decimal strings with two decimals (EGP).
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'can_cancel' => $this->canBeCancelled(),
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->getTranslation('name', app()->getLocale()),
                'is_food' => $this->category->is_food,
            ]),
            'details' => $this->details,
            'pickup' => [
                'address' => $this->pickup_address,
                'lat' => $this->pickup_lat,
                'lng' => $this->pickup_lng,
                'contact_phone' => $this->pickup_contact_phone,
            ],
            'dropoff' => [
                'address' => $this->dropoff_address,
                'lat' => $this->dropoff_lat,
                'lng' => $this->dropoff_lng,
            ],
            'receiver' => [
                'name' => $this->receiver_name,
                'phone' => $this->receiver_phone,
            ],
            'goods_value' => $this->goods_value,
            'shipping_paid_by' => $this->shipping_paid_by,
            'amount_to_collect' => $this->amount_to_collect,
            'distance_km' => $this->distance_km,
            'food_extra' => $this->food_extra,
            'shipping_fee' => $this->shipping_fee,
            'cancel_reason' => $this->whenLoaded('cancelReason', fn (): ?array => $this->cancelReason === null ? null : [
                'id' => $this->cancelReason->id,
                'name' => $this->cancelReason->getTranslation('name', app()->getLocale()),
            ]),
            'price_accepted_at' => $this->price_accepted_at?->toIso8601ZuluString(),
            'cancelled_at' => $this->cancelled_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
