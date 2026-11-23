<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Category;

class RemoveCategoryIconAction
{
    /**
     * Removing a missing icon is fine.
     */
    public function handle(Category $category): Category
    {
        $category->clearMediaCollection(Category::ICON_COLLECTION);

        return $category->load('media');
    }
}
