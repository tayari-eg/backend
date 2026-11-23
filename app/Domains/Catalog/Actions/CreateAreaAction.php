<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Area;

class CreateAreaAction
{
    /**
     * An area without a sort order goes to the end of the list.
     *
     * @param  array{name: array{ar: string, en: string}, city: array{ar: string, en: string}, center_lat: string, center_lng: string, radius_km: string, is_active?: bool, sort_order?: int}  $attributes
     */
    public function handle(array $attributes): Area
    {
        return Area::query()->create([
            'is_active' => true,
            ...$attributes,
            'sort_order' => $attributes['sort_order'] ?? (int) Area::query()->max('sort_order') + 1,
        ]);
    }
}
