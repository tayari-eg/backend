<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCategoryAction
{
    /**
     * SQLSTATE codes for a foreign key violation on MySQL (23000) and PostgreSQL (23503).
     */
    private const array FOREIGN_KEY_VIOLATIONS = ['23000', '23503'];

    /**
     * Its icon is deleted with it.
     *
     * @throws ValidationException when it has subcategories
     */
    public function handle(Category $category): void
    {
        try {
            DB::transaction(function () use ($category): void {
                $category = Category::query()->lockForUpdate()->findOrFail($category->id);

                if ($category->children()->exists()) {
                    throw $this->inUse();
                }

                $category->delete();
            });
        } catch (QueryException $exception) {
            // A subcategory created at the same moment still holds the foreign key.
            if (! in_array((string) $exception->getCode(), self::FOREIGN_KEY_VIOLATIONS, true)) {
                throw $exception;
            }

            throw $this->inUse();
        }
    }

    private function inUse(): ValidationException
    {
        return ValidationException::withMessages(['category' => __('admin.category_in_use')]);
    }
}
