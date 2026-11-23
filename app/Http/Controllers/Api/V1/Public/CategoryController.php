<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domains\Catalog\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\ListCategoriesRequest;
use App\Http\Resources\Api\V1\Public\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * Active main categories with their active subcategories, with names in the request locale.
     *
     * @unauthenticated
     */
    public function index(ListCategoriesRequest $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->main()
            ->active()
            // An order needs a subcategory, so a main category without an active one is hidden.
            ->whereHas('children', fn ($query) => $query->active())
            ->with([
                'media',
                'children' => fn ($query) => $query->active()->with('media')->orderBy('sort_order')->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return CategoryResource::collection($categories);
    }
}
