<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Area;

class UpdateAreaAction
{
    /**
     * Running work sessions keep the area as it was when they started; only new sessions see the change.
     *
     * @param  array{name?: array{ar: string, en: string}, city?: array{ar: string, en: string}, center_lat?: string, center_lng?: string, radius_km?: string, is_active?: bool, sort_order?: int}  $attributes
     */
    public function handle(Area $area, array $attributes): Area
    {
        $area->update($attributes);

        return $area;
    }
}
