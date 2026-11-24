<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Orders\Models\OrderRating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A rating as the one who gave it sees it, on the business's and the tayar's order.
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
            'stars' => $this->stars,
            'comment' => $this->comment,
            // Staff hid the comment from the other party.
            'comment_hidden' => $this->isCommentHidden(),
            // Until then it can be changed and the other party can't see it.
            'editable_until' => $this->revealed_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
