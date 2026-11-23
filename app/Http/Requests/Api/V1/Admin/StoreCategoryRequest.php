<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Catalog\Models\Category;
use App\Http\Requests\Concerns\ValidatesUniqueTranslatedNames;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    use ValidatesUniqueTranslatedNames;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Names are unique among the categories under the same parent.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $parentId = $this->parentId();

        return [
            'parent_id' => ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')->whereNull('parent_id')],
            ...$this->uniqueTranslatedNameRules('required', Category::class, 'admin.category_name_taken', null, fn ($query) => $query->under($parentId)),
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
        return ['parent_id.exists' => __('admin.category_parent_must_be_main')];
    }

    public function parentId(): ?int
    {
        $parentId = $this->input('parent_id');

        return is_numeric($parentId) ? (int) $parentId : null;
    }
}
