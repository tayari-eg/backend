<?php

namespace App\Http\Resources\Api\V1\Auth;

use App\Domains\Accounts\Enums\DocumentCollection;
use App\Http\Resources\Api\V1\Client\BusinessProfileResource;
use App\Http\Resources\Api\V1\MediaResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Relations the resource reads; load them first so nothing is lazy loaded.
     *
     * @var list<string>
     */
    public const array RELATIONS = ['media', 'roles', 'businessProfile.businessType', 'businessProfile.links', 'businessProfile.media'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isStaff = $this->relationLoaded('roles') && $this->roles->isNotEmpty();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'account_type' => $this->account_type,
            'locale' => $this->locale,
            'status' => $this->status,
            'is_phone_verified' => $this->hasVerifiedPhone(),
            'phone_verified_at' => $this->phone_verified_at?->toIso8601ZuluString(),
            'national_id' => $this->whenLoaded('media', fn (): array => [
                'status' => $this->nationalIdStatus(),
                'rejection_reason' => $this->id_rejection_reason,
                'verified_at' => $this->id_verified_at?->toIso8601ZuluString(),
                'front' => MediaResource::optional($this->getFirstMedia(DocumentCollection::NationalIdFront->value)),
                'back' => MediaResource::optional($this->getFirstMedia(DocumentCollection::NationalIdBack->value)),
            ]),
            'business_profile' => BusinessProfileResource::make($this->whenLoaded('businessProfile')),
            'roles' => $this->when($isStaff, fn () => $this->roles->pluck('name')->values()),
            'permissions' => $this->when($isStaff, fn () => $this->getAllPermissions()->pluck('name')->values()),
        ];
    }
}
