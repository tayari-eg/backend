<?php

namespace App\Http\Resources\Api\V1\Tayar;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An order the tayar accepted, with the people to call while it is active; once it is past, only their names stay.
 * Amounts are decimal strings with two decimals (EGP).
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
        $isActive = $this->status->isActive();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'mode' => $this->whenLoaded('mode', fn (): array => [
                'code' => $this->mode->code->value,
                'name' => $this->mode->getTranslation('name', app()->getLocale()),
            ]),
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->getTranslation('name', app()->getLocale()),
                'is_food' => $this->category->is_food,
            ]),
            'details' => $this->details,
            'sender' => $this->whenLoaded('sender', fn (): array => [
                'name' => $this->sender->name,
                'phone' => $isActive ? $this->sender->phone : null,
                'business_name' => $this->sender->businessProfile?->business_name,
            ]),
            'pickup' => [
                'address' => $this->pickup_address,
                'lat' => $this->pickup_lat,
                'lng' => $this->pickup_lng,
                'contact_phone' => $isActive ? $this->pickup_contact_phone : null,
            ],
            'dropoff' => [
                'address' => $this->dropoff_address,
                'lat' => $this->dropoff_lat,
                'lng' => $this->dropoff_lng,
            ],
            'receiver' => [
                'name' => $this->receiver_name,
                'phone' => $isActive ? $this->receiver_phone : null,
            ],
            'distance_km' => $this->distance_km,
            'tayar_earning' => $this->tayar_earning,
            // What the sender owes this tayar for cancelling after the free period; the wallet settles it later.
            'cancel_fee' => $this->cancel_fee_owed_to_tayar_id !== null && $this->cancel_fee_owed_to_tayar_id === $request->user()?->id ? $this->cancel_fee : '0.00',
            'pickup_photos' => $this->whenLoaded('media', fn () => MediaResource::collection($this->pickupPhotos())),
            // The codes themselves only go to the receiver; the tayar sees whether they can be checked.
            'receiver_codes' => [
                'sent_at' => $this->codes_sent_at?->toIso8601ZuluString(),
                'expire_at' => $this->status->hasReceiverCodes() ? $this->codes_expire_at?->toIso8601ZuluString() : null,
                'attempts_left' => $this->codeAttemptsLeft(),
                'problem' => $this->status === OrderStatus::Ongoing ? $this->receiverCodesProblem() : null,
            ],
            'not_delivered_reason' => $this->whenLoaded('notDeliveredReason', fn (): ?array => $this->notDeliveredReason === null ? null : [
                'id' => $this->notDeliveredReason->id,
                'name' => $this->notDeliveredReason->getTranslation('name', app()->getLocale()),
            ]),
            'not_delivered_note' => $this->not_delivered_note,
            'accepted_at' => $this->accepted_at?->toIso8601ZuluString(),
            'picked_up_at' => $this->picked_up_at?->toIso8601ZuluString(),
            'receiver_confirmed_at' => $this->receiver_confirmed_at?->toIso8601ZuluString(),
            'delivered_at' => $this->delivered_at?->toIso8601ZuluString(),
            'not_delivered_at' => $this->not_delivered_at?->toIso8601ZuluString(),
            'cancelled_at' => $this->cancelled_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
