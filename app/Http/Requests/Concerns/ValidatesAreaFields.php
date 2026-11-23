<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Catalog\Models\Area;

trait ValidatesAreaFields
{
    /**
     * City in every language, the centre point and the radius, plus the active flag and sort order.
     *
     * @return array<string, mixed>
     */
    protected function areaFieldRules(string $presence): array
    {
        $rules = [
            'city' => [$presence, 'array:'.implode(',', Locale::values())],
            'center_lat' => [$presence, 'numeric', 'decimal:0,7', 'between:-90,90'],
            'center_lng' => [$presence, 'numeric', 'decimal:0,7', 'between:-180,180'],
            'radius_km' => [$presence, 'numeric', 'decimal:0,2', 'min:'.Area::MIN_RADIUS_KM, 'max:'.Area::MAX_RADIUS_KM],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];

        foreach (Locale::values() as $locale) {
            $rules["city.{$locale}"] = ['required_with:city', 'string', 'max:100'];
        }

        return $rules;
    }
}
