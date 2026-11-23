<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Catalog\Actions\ActivateCategoryAction;
use App\Domains\Catalog\Actions\CreateCategoryAction;
use App\Domains\Catalog\Actions\DeactivateCategoryAction;
use App\Domains\Catalog\Actions\DeleteCategoryAction;
use App\Domains\Catalog\Actions\UpdateCategoryAction;
use App\Domains\Catalog\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListCategoriesRequest;
use App\Http\Requests\Api\V1\Admin\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\Admin\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class CategoryController extends Controller
{
    /**
     * Main categories and subcategories, active and inactive, in sort order.
     */
    public function index(ListCategoriesRequest $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->with(['parent', 'media'])
            ->withCount('children')
            ->when($request->isActive() !== null, fn ($query) => $query->where('is_active', $request->isActive()))
            ->when($request->isFood() !== null, fn ($query) => $query->where('is_food', $request->isFood()))
            ->when($request->mainOnly(), fn ($query) => $query->main())
            ->when($request->parentId() !== null, fn ($query) => $query->where('parent_id', $request->parentId()))
            ->when($request->search() !== null, fn ($query) => $query->whereNameLike($request->search()))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return CategoryResource::collection($categories);
    }

    /**
     * Show one category with its names in every language, its parent and its icon.
     */
    public function show(Category $category): CategoryResource
    {
        return self::present($category);
    }

    /**
     * Create a main category, or a subcategory with parent_id.
     */
    public function store(StoreCategoryRequest $request, CreateCategoryAction $createCategory): JsonResponse
    {
        $category = $createCategory->handle(
            $request->validated('name'),
            $request->parentId(),
            $request->has('is_food') ? $request->boolean('is_food') : null,
            $request->boolean('is_active', true),
            $request->has('sort_order') ? (int) $request->validated('sort_order') : null,
        );

        return self::present($category)->response()->setStatusCode(HttpStatus::HTTP_CREATED);
    }

    /**
     * Update or move a category; send only the fields that change.
     */
    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategoryAction $updateCategory): CategoryResource
    {
        return self::present($updateCategory->handle($category, $request->validated()));
    }

    /**
     * Show a category in the public list again.
     */
    public function activate(Category $category, ActivateCategoryAction $activateCategory): CategoryResource
    {
        return self::present($activateCategory->handle($category));
    }

    /**
     * Hide a category (and, for a main category, its subcategories) from the public list.
     */
    public function deactivate(Category $category, DeactivateCategoryAction $deactivateCategory): CategoryResource
    {
        return self::present($deactivateCategory->handle($category));
    }

    /**
     * Delete a category that has no subcategories.
     */
    public function destroy(Category $category, DeleteCategoryAction $deleteCategory): Response
    {
        $deleteCategory->handle($category);

        return response()->noContent();
    }

    private static function present(Category $category): CategoryResource
    {
        return CategoryResource::make($category->load(['parent', 'media'])->loadCount('children'));
    }
}
