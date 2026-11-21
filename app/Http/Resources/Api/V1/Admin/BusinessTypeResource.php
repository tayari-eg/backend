<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Models\BusinessType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A business type as staff edit it: the name in every language.
 *
 * @mixin BusinessType
 */
class BusinessTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => collect(Locale::values())
                ->mapWithKeys(fn (string $locale): array => [$locale => $this->getTranslation('name', $locale, false)])
                ->all(),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'business_profiles_count' => $this->whenCounted('businessProfiles'),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
