<?php

namespace App\Http\Resources\Api\V1\Client;

use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Http\Resources\Api\V1\MediaResource;
use App\Http\Resources\Api\V1\Public\BusinessTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BusinessProfile
 */
class BusinessProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_type' => BusinessTypeResource::make($this->whenLoaded('businessType')),
            'business_name' => $this->business_name,
            'description' => $this->description,
            'avg_daily_orders' => $this->avg_daily_orders,
            'tax_registration_number' => $this->tax_registration_number,
            'verification_status' => $this->verification_status,
            'rejection_reason' => $this->rejection_reason,
            'reviewed_at' => $this->reviewed_at?->toIso8601ZuluString(),
            'links' => BusinessLinkResource::collection($this->whenLoaded('links')),
            'documents' => $this->whenLoaded('media', fn (): array => collect(DocumentCollection::forBusinessProfile())
                ->mapWithKeys(fn (DocumentCollection $collection): array => [
                    $collection->value => MediaResource::optional($this->getFirstMedia($collection->value)),
                ])
                ->all()),
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
            ]),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
