<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Tayars\Enums\TayarDocument;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A tayar application. The owner, the reviewer and the review history only appear when loaded (admin screens).
 *
 * @mixin TayarProfile
 */
class TayarProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'can_reapply_at' => $this->can_reapply_at?->toIso8601ZuluString(),
            'reviewed_at' => $this->reviewed_at?->toIso8601ZuluString(),
            'national_id_number' => $this->national_id_number,
            'is_online' => $this->is_online,
            'location' => $this->current_lat === null ? null : [
                'lat' => (float) $this->current_lat,
                'lng' => (float) $this->current_lng,
                'updated_at' => $this->location_updated_at?->toIso8601ZuluString(),
            ],
            'documents' => $this->whenLoaded('media', fn (): array => collect(TayarDocument::cases())
                ->mapWithKeys(fn (TayarDocument $document): array => [
                    $document->value => MediaResource::optional($this->getFirstMedia($document->value)),
                ])
                ->all()),
            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle')),
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
                'status' => $this->user->status,
            ]),
            'reviewed_by' => $this->whenLoaded('reviewer', fn (): ?array => $this->reviewer === null ? null : [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ]),
            'reviews' => $this->whenLoaded('reviews', fn (): array => $this->reviews->map(fn (TayarReview $review): array => [
                'decision' => $review->decision,
                'reason' => $review->reason,
                'reviewed_by' => $review->reviewer === null ? null : ['id' => $review->reviewer->id, 'name' => $review->reviewer->name],
                'created_at' => $review->created_at?->toIso8601ZuluString(),
            ])->all()),
            // Only the admin screens load the rank.
            'standing' => $this->whenLoaded('rank', fn (): array => [
                'rank' => $this->rank === null ? null : [
                    'id' => $this->rank->id,
                    'code' => $this->rank->code,
                    'name' => $this->rank->getTranslation('name', app()->getLocale()),
                    'level' => $this->rank->level,
                    'color' => $this->rank->color,
                ],
                'rank_below_since' => $this->rank_below_since?->toIso8601ZuluString(),
                'delivered_orders_30d' => $this->delivered_orders_30d,
                'ratings_count_30d' => $this->ratings_count_30d,
                'rating_avg' => $this->rating_avg,
                'updated_at' => $this->standing_updated_at?->toIso8601ZuluString(),
            ]),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
