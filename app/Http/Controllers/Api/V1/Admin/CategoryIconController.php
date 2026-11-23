<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Catalog\Actions\RemoveCategoryIconAction;
use App\Domains\Catalog\Actions\UploadCategoryIconAction;
use App\Domains\Catalog\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreCategoryIconRequest;
use App\Http\Resources\Api\V1\Admin\CategoryResource;

class CategoryIconController extends Controller
{
    /**
     * Upload or replace a category's icon (multipart).
     */
    public function store(StoreCategoryIconRequest $request, Category $category, UploadCategoryIconAction $uploadIcon): CategoryResource
    {
        return CategoryResource::make($uploadIcon->handle($category, $request->file('icon'))->load('parent')->loadCount('children'));
    }

    /**
     * Remove a category's icon.
     */
    public function destroy(Category $category, RemoveCategoryIconAction $removeIcon): CategoryResource
    {
        return CategoryResource::make($removeIcon->handle($category)->load('parent')->loadCount('children'));
    }
}
