<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Domains\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A main category with its active subcategories, or (inside children) one subcategory.
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
            'name' => $this->getTranslation('name', app()->getLocale()),
            'is_food' => $this->is_food,
            'icon_url' => $this->iconUrl(),
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
