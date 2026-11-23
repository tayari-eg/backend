<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Catalog\Models\Category;
use App\Domains\Pricing\Data\GeoPoint;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * The fields that price an order: the subcategory and the pickup and drop-off points.
 */
trait ValidatesOrderRoute
{
    /**
     * @return array<string, mixed>
     */
    protected function orderRouteRules(): array
    {
        $lat = ['required', 'numeric', 'decimal:0,7', 'between:-90,90'];
        $lng = ['required', 'numeric', 'decimal:0,7', 'between:-180,180'];

        return [
            // An active subcategory of an active main category, as the public category list shows them.
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')->where(fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereNotNull('parent_id')
                ->whereIn('parent_id', fn (Builder $parents) => $parents->select('id')->from('categories')->where('is_active', true)))],
            'pickup_lat' => $lat,
            'pickup_lng' => $lng,
            'dropoff_lat' => $lat,
            'dropoff_lng' => $lng,
        ];
    }

    public function pickup(): GeoPoint
    {
        return new GeoPoint((string) $this->validated('pickup_lat'), (string) $this->validated('pickup_lng'));
    }

    public function dropoff(): GeoPoint
    {
        return new GeoPoint((string) $this->validated('dropoff_lat'), (string) $this->validated('dropoff_lng'));
    }

    public function category(): Category
    {
        return Category::query()->findOrFail((int) $this->validated('category_id'));
    }
}
