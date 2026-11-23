<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCategoryAction
{
    /**
     * A category without a sort order goes to the end of its list. A subcategory without is_food takes its parent's.
     *
     * @param  array{ar: string, en: string}  $name
     *
     * @throws ValidationException when the parent is itself a subcategory
     */
    public function handle(array $name, ?int $parentId, ?bool $isFood, bool $isActive, ?int $sortOrder): Category
    {
        return DB::transaction(function () use ($name, $parentId, $isFood, $isActive, $sortOrder): Category {
            // Locked so the parent can't become a subcategory at the same moment.
            $parent = $parentId === null ? null : Category::query()->lockForUpdate()->findOrFail($parentId);

            if ($parent !== null && ! $parent->isMain()) {
                throw ValidationException::withMessages(['parent_id' => __('admin.category_parent_must_be_main')]);
            }

            return Category::query()->create([
                'parent_id' => $parentId,
                'name' => $name,
                'is_food' => $isFood ?? ($parent?->is_food ?? false),
                'is_active' => $isActive,
                'sort_order' => $sortOrder ?? (int) Category::query()->under($parentId)->max('sort_order') + 1,
            ]);
        });
    }
}
