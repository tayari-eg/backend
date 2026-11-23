<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Category;

class DeactivateCategoryAction
{
    /**
     * Hides it from the public list; an inactive main category also hides its subcategories.
     */
    public function handle(Category $category): Category
    {
        $category->forceFill(['is_active' => false])->save();

        return $category;
    }
}
