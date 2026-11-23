<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Category;

class ActivateCategoryAction
{
    public function handle(Category $category): Category
    {
        $category->forceFill(['is_active' => true])->save();

        return $category;
    }
}
