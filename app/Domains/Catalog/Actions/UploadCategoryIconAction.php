<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Category;
use Illuminate\Http\UploadedFile;

class UploadCategoryIconAction
{
    /**
     * Replaces the current icon, if any.
     */
    public function handle(Category $category, UploadedFile $icon): Category
    {
        $category->addMedia($icon)->toMediaCollection(Category::ICON_COLLECTION);

        return $category->load('media');
    }
}
