<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Orders\Models\OrderRating;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A rating as staff see it: both parties, the full comment even when hidden, and who hid it and why.
 *
 * @mixin OrderRating
 */
class OrderRatingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->whenLoaded('order', fn (): array => ['id' => $this->order->id, 'code' => $this->order->code]),
            'rater_role' => $this->rater_role,
            'rater' => $this->whenLoaded('rater', fn (): ?array => self::person($this->rater)),
            'rated_user' => $this->whenLoaded('ratedUser', fn (): ?array => self::person($this->ratedUser)),
            'stars' => $this->stars,
            'comment' => $this->comment,
            'comment_hidden_at' => $this->comment_hidden_at?->toIso8601ZuluString(),
            'comment_hidden_reason' => $this->comment_hidden_reason,
            'comment_hidden_by' => $this->whenLoaded('commentHider', fn (): ?array => self::person($this->commentHider)),
            'is_revealed' => $this->isRevealed(now()),
            'revealed_at' => $this->revealed_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * @return array{id: int, name: string, phone: string}|null
     */
    private static function person(?User $user): ?array
    {
        return $user === null ? null : ['id' => $user->id, 'name' => $user->name, 'phone' => $user->phone];
    }
}
