<?php

namespace App\Http\Resources\Api\V1\Tayar;

use App\Domains\Orders\Models\OrderRating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A revealed rating the tayar received, without the order or the business, so the rater stays unknown. Only the
 * business-time date shows, not the time, for the same reason.
 *
 * @mixin OrderRating
 */
class ReceivedRatingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stars' => $this->stars,
            'comment' => $this->visibleComment(),
            'date' => $this->revealed_at->timezone(config('app.business_timezone'))->format('Y-m-d'),
        ];
    }
}
