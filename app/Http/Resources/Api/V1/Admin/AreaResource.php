<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Catalog\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An area as staff edit it: name and city in every language, the centre and the radius.
 *
 * @mixin Area
 */
class AreaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->translationsOf('name'),
            'city' => $this->translationsOf('city'),
            'center' => ['lat' => (float) $this->center_lat, 'lng' => (float) $this->center_lng],
            'radius_km' => (float) $this->radius_km,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'work_sessions_count' => $this->whenCounted('workSessions'),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function translationsOf(string $attribute): array
    {
        return collect(Locale::values())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->getTranslation($attribute, $locale, false)])
            ->all();
    }
}
