<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Orders\Enums\OrderCanceller;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An order as staff see it, with the full fee breakdown and the tayar once one accepts. tayar_earning and platform_fee
 * are estimates from the general rule until a tayar accepts; then they are the accepting tayar's. Amounts are decimal
 * strings with two decimals (EGP).
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
            'can_cancel' => $this->canBeCancelledBy(OrderCanceller::Staff),
            'sender' => $this->whenLoaded('sender', fn (): array => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'phone' => $this->sender->phone,
                'business_name' => $this->sender->businessProfile?->business_name,
            ]),
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => self::translations($this->category, 'name'),
                'is_food' => $this->category->is_food,
            ]),
            'mode' => $this->whenLoaded('mode', fn (): array => [
                'id' => $this->mode->id,
                'code' => $this->mode->code->value,
                'name' => self::translations($this->mode, 'name'),
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
                'user_id' => $this->receiver_user_id,
            ],
            'goods_value' => $this->goods_value,
            'shipping_paid_by' => $this->shipping_paid_by,
            'amount_to_collect' => $this->amount_to_collect,
            'fee' => [
                'distance_km' => $this->distance_km,
                'base_fare' => $this->base_fare,
                'price_per_km' => $this->price_per_km,
                'distance_fare' => $this->distance_fare,
                'minimum_fare' => $this->minimum_fare,
                'food_extra' => $this->food_extra,
                'shipping_fee' => $this->shipping_fee,
                'tayar_share_percent' => $this->tayar_share_percent,
                'tayar_earning' => $this->tayar_earning,
                'platform_fee' => $this->platform_fee,
            ],
            'tayar' => $this->whenLoaded('tayar', fn (): ?array => $this->tayar === null ? null : [
                'id' => $this->tayar->id,
                'name' => $this->tayar->name,
                'phone' => $this->tayar->phone,
            ]),
            'vehicle' => $this->whenLoaded('workSession', fn (): ?array => $this->workSession === null ? null : [
                'id' => $this->workSession->vehicle->id,
                'type' => self::translations($this->workSession->vehicle->vehicleType, 'name'),
                'model' => $this->workSession->vehicle->model,
                'color' => $this->workSession->vehicle->color,
                'plate_number' => $this->workSession->vehicle->plate_number,
            ]),
            'work_session_id' => $this->work_session_id,
            'cancelled_by_type' => $this->cancelledByType(),
            'cancelled_by' => $this->whenLoaded('canceller', fn (): ?array => self::person($this->canceller)),
            'cancel_fee' => $this->cancel_fee,
            'cancel_fee_owed_to' => $this->whenLoaded('cancelFeeOwedTo', fn (): ?array => self::person($this->cancelFeeOwedTo)),
            'cancel_reason' => $this->whenLoaded('cancelReason', fn (): ?array => $this->cancelReason === null ? null : [
                'id' => $this->cancelReason->id,
                'name' => self::translations($this->cancelReason, 'name'),
            ]),
            'status_history' => $this->whenLoaded('statusHistories', fn (): array => $this->statusHistories
                ->map(fn (OrderStatusHistory $history): array => [
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'changed_by' => self::person($history->changer),
                    'note' => $history->note,
                    'created_at' => $history->created_at?->toIso8601ZuluString(),
                ])
                ->all()),
            'price_accepted_at' => $this->price_accepted_at?->toIso8601ZuluString(),
            'accepted_at' => $this->accepted_at?->toIso8601ZuluString(),
            'cancelled_at' => $this->cancelled_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private static function person(?User $user): ?array
    {
        return $user === null ? null : ['id' => $user->id, 'name' => $user->name];
    }

    /**
     * @return array<string, string|null>
     */
    private static function translations(Model $model, string $attribute): array
    {
        return collect(Locale::values())
            ->mapWithKeys(fn (string $locale): array => [$locale => $model->getTranslation($attribute, $locale, false)])
            ->all();
    }
}
