<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A category as staff edit it: the name in every language, its parent and its icon.
 *
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn (): ?array => $this->parent === null ? null : [
                'id' => $this->parent->id,
                'name' => self::translations($this->parent),
            ]),
            'name' => self::translations($this->resource),
            'is_food' => $this->is_food,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'icon_url' => $this->whenLoaded('media', fn (): ?string => $this->iconUrl()),
            'children_count' => $this->whenCounted('children'),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function translations(Category $category): array
    {
        return collect(Locale::values())
            ->mapWithKeys(fn (string $locale): array => [$locale => $category->getTranslation('name', $locale, false)])
            ->all();
    }
}
