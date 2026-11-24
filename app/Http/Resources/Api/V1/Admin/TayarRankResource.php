<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Incentives\Models\TayarRank;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A tayar rank as staff edit it: the name in every language.
 *
 * @mixin TayarRank
 */
class TayarRankResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => collect(Locale::values())
                ->mapWithKeys(fn (string $locale): array => [$locale => $this->getTranslation('name', $locale, false)])
                ->all(),
            'level' => $this->level,
            'min_delivered_orders_30d' => $this->min_delivered_orders_30d,
            'min_rating' => $this->min_rating,
            'color' => $this->color,
            'tayars_count' => $this->whenCounted('tayarProfiles'),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
