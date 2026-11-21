<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\DocumentCollection;
use App\Http\Resources\Api\V1\MediaResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A client's national ID as staff see it for review.
 *
 * @mixin User
 */
class NationalIdReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $front = $this->getFirstMedia(DocumentCollection::NationalIdFront->value);

        return [
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'phone' => $this->phone,
                'account_type' => $this->account_type,
            ],
            'status' => $this->nationalIdStatus(),
            'rejection_reason' => $this->id_rejection_reason,
            'verified_at' => $this->id_verified_at?->toIso8601ZuluString(),
            'reviewed_at' => $this->id_reviewed_at?->toIso8601ZuluString(),
            'submitted_at' => $front?->created_at?->toIso8601ZuluString(),
            'front' => MediaResource::optional($front),
            'back' => MediaResource::optional($this->getFirstMedia(DocumentCollection::NationalIdBack->value)),
        ];
    }
}
