<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\DocumentCollection;
use App\Http\Resources\Api\V1\Client\BusinessProfileResource;
use App\Http\Resources\Api\V1\MediaResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A customer or business account as staff see it.
 *
 * @mixin User
 */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'account_type' => $this->account_type,
            'locale' => $this->locale,
            'status' => $this->status,
            'blocked_reason' => $this->blocked_reason,
            'is_phone_verified' => $this->hasVerifiedPhone(),
            'phone_verified_at' => $this->phone_verified_at?->toIso8601ZuluString(),
            'national_id' => $this->whenLoaded('media', fn (): array => [
                'status' => $this->nationalIdStatus(),
                'rejection_reason' => $this->id_rejection_reason,
                'verified_at' => $this->id_verified_at?->toIso8601ZuluString(),
                'reviewed_at' => $this->id_reviewed_at?->toIso8601ZuluString(),
                'front' => MediaResource::optional($this->getFirstMedia(DocumentCollection::NationalIdFront->value)),
                'back' => MediaResource::optional($this->getFirstMedia(DocumentCollection::NationalIdBack->value)),
            ]),
            'business_profile' => BusinessProfileResource::make($this->whenLoaded('businessProfile')),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
