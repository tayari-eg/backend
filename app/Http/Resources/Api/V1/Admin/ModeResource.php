<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Catalog\Models\Mode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A mode as staff edit it: name and description in every language.
 *
 * @mixin Mode
 */
class ModeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code->value,
            'name' => $this->translationsOf('name'),
            'description' => $this->getTranslations('description') === [] ? null : $this->translationsOf('description'),
            'allows_food' => $this->allows_food,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'work_sessions_count' => $this->whenCounted('workSessions'),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function translationsOf(string $attribute): array
    {
        return collect(Locale::values())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->getTranslation($attribute, $locale, false)])
            ->all();
    }
}
