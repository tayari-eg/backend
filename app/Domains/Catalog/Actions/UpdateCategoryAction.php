<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Catalog\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCategoryAction
{
    /**
     * Moving a category keeps two levels at most: the new parent must be a main category, and a category with
     * subcategories can't move under another one. Its name must stay unique among its new siblings.
     *
     * @param  array{name?: array{ar: string, en: string}, parent_id?: int|null, is_food?: bool, is_active?: bool, sort_order?: int}  $attributes
     *
     * @throws ValidationException when the move would make a third level, or a sibling has the same name
     */
    public function handle(Category $category, array $attributes): Category
    {
        return DB::transaction(function () use ($category, $attributes): Category {
            $requestedParentId = array_key_exists('parent_id', $attributes) ? self::toId($attributes['parent_id']) : null;

            // One query in id order, so two opposite moves lock their rows in the same order and can't deadlock.
            $locked = Category::query()
                ->whereKey(array_values(array_filter([$category->id, $requestedParentId])))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $category = $locked->get($category->id) ?? throw (new ModelNotFoundException)->setModel(Category::class, [$category->id]);
            $parentId = array_key_exists('parent_id', $attributes) ? $requestedParentId : $category->parent_id;
            $attributes['parent_id'] = $parentId;

            if ($parentId !== null && $parentId !== $category->parent_id) {
                $parent = $locked->get($parentId) ?? throw (new ModelNotFoundException)->setModel(Category::class, [$parentId]);

                if (! $parent->isMain() || $parent->is($category)) {
                    throw ValidationException::withMessages(['parent_id' => __('admin.category_parent_must_be_main')]);
                }

                if ($category->children()->exists()) {
                    throw ValidationException::withMessages(['parent_id' => __('admin.category_has_subcategories')]);
                }
            }

            if ($parentId !== $category->parent_id) {
                $this->ensureUniqueAmongSiblings($category, $parentId, $attributes['name'] ?? $category->getTranslations('name'));
            }

            $category->update($attributes);

            return $category;
        });
    }

    /**
     * The FormRequest checks names against the siblings it knows; a move without a new name is checked here.
     *
     * @param  array<string, string>  $name
     */
    private function ensureUniqueAmongSiblings(Category $category, ?int $parentId, array $name): void
    {
        foreach (Locale::values() as $locale) {
            $isTaken = isset($name[$locale]) && Category::query()
                ->whereKeyNot($category->id)
                ->under($parentId)
                ->whereNameIs($locale, $name[$locale])
                ->exists();

            if ($isTaken) {
                throw ValidationException::withMessages(["name.{$locale}" => __('admin.category_name_taken')]);
            }
        }
    }

    private static function toId(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
