<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Catalog\Models\Category;
use App\Http\Requests\Concerns\ValidatesUniqueTranslatedNames;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    use ValidatesUniqueTranslatedNames;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Send only the fields that change; a name always needs both languages and is unique under its (new) parent.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');
        $parentId = $this->has('parent_id')
            ? (is_numeric($this->input('parent_id')) ? (int) $this->input('parent_id') : null)
            : $category?->parent_id;

        return [
            'parent_id' => ['sometimes', 'nullable', 'integer', Rule::notIn([$category?->id]), Rule::exists('categories', 'id')->whereNull('parent_id')],
            ...$this->uniqueTranslatedNameRules('sometimes', Category::class, 'admin.category_name_taken', $category?->id, fn ($query) => $query->under($parentId)),
            'is_food' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.exists' => __('admin.category_parent_must_be_main'),
            'parent_id.not_in' => __('admin.category_parent_must_be_main'),
        ];
    }
}
