<?php

namespace App\Http\Resources\Api\V1\Client;

use App\Domains\Orders\Enums\OrderCanceller;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Http\Resources\Api\V1\MediaResource;
use App\Http\Resources\Api\V1\OrderRatingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An order as its sender sees it, with the tayar once one accepts. Amounts are decimal strings with two decimals (EGP).
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
            'can_cancel' => $this->canBeCancelledBy(OrderCanceller::Sender),
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
            'tayar' => $this->whenLoaded('tayar', fn (): ?array => $this->tayar === null ? null : [
                'name' => $this->tayar->name,
                // Only while the order is active; once it is past, the name stays.
                'phone' => $this->status->isActive() ? $this->tayar->phone : null,
                'vehicle' => $this->whenLoaded('workSession', fn (): ?array => $this->workSession === null ? null : [
                    'type' => $this->workSession->vehicle->vehicleType->getTranslation('name', app()->getLocale()),
                    'model' => $this->workSession->vehicle->model,
                    'color' => $this->workSession->vehicle->color,
                    'plate_number' => $this->workSession->vehicle->plate_number,
                ]),
                // The last position the tayar sent, while the order is accepted, picked up or on the way.
                'location' => $this->whenLoaded('tayar', fn (): ?array => in_array($this->status, OrderStatus::withTayarCases(), true)
                    && $this->tayar->relationLoaded('tayarProfile') && $this->tayar->tayarProfile?->current_lat !== null ? [
                        'lat' => $this->tayar->tayarProfile->current_lat,
                        'lng' => $this->tayar->tayarProfile->current_lng,
                        'updated_at' => $this->tayar->tayarProfile->location_updated_at?->toIso8601ZuluString(),
                    ] : null),
            ]),
            'pickup_photos' => $this->whenLoaded('media', fn () => MediaResource::collection($this->pickupPhotos())),
            'not_delivered_reason' => $this->whenLoaded('notDeliveredReason', fn (): ?array => $this->notDeliveredReason === null ? null : [
                'id' => $this->notDeliveredReason->id,
                'name' => $this->notDeliveredReason->getTranslation('name', app()->getLocale()),
            ]),
            'not_delivered_note' => $this->not_delivered_note,
            // For the receiver's tracking link, if the sender wants to share it.
            'tracking_token' => $this->tracking_token,
            // Within the rating window after delivery; my_rating is my own rating only, never the other party's.
            'can_rate' => $this->canBeRatedNow(),
            'rating_ends_at' => $this->ratingEndsAt()?->toIso8601ZuluString(),
            'my_rating' => $this->whenLoaded('ratings', fn (): ?OrderRatingResource => ($rating = $this->ratings->firstWhere('rater_id', $request->user()?->id)) === null
                ? null
                : OrderRatingResource::make($rating)),
            'cancelled_by_type' => $this->cancelledByType(),
            // Recorded on cancel: what the sender owes the tayar for cancelling after the free period.
            'cancel_fee' => $this->cancel_fee,
            'free_cancel_until' => $this->freeCancelUntil()?->toIso8601ZuluString(),
            'cancel_fee_now' => (string) $this->senderCancelFeeAt(now()),
            'search_ends_at' => $this->status === OrderStatus::LookingForTayar ? $this->searchEndsAt()->toIso8601ZuluString() : null,
            'price_accepted_at' => $this->price_accepted_at?->toIso8601ZuluString(),
            'accepted_at' => $this->accepted_at?->toIso8601ZuluString(),
            'picked_up_at' => $this->picked_up_at?->toIso8601ZuluString(),
            'receiver_confirmed_at' => $this->receiver_confirmed_at?->toIso8601ZuluString(),
            'codes_sent_at' => $this->codes_sent_at?->toIso8601ZuluString(),
            'delivered_at' => $this->delivered_at?->toIso8601ZuluString(),
            'not_delivered_at' => $this->not_delivered_at?->toIso8601ZuluString(),
            'cancelled_at' => $this->cancelled_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
