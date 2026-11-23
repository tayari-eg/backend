<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\PaginatesResults;
use Illuminate\Foundation\Http\FormRequest;

class ListCategoriesRequest extends FormRequest
{
    use PaginatesResults;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Query strings send booleans as text.
            'is_active' => ['sometimes', 'in:true,false,1,0'],
            'is_food' => ['sometimes', 'in:true,false,1,0'],
            'main_only' => ['sometimes', 'in:true,false,1,0'],
            'parent_id' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            ...$this->paginationRules(),
        ];
    }

    public function isActive(): ?bool
    {
        return $this->has('is_active') ? $this->boolean('is_active') : null;
    }

    public function isFood(): ?bool
    {
        return $this->has('is_food') ? $this->boolean('is_food') : null;
    }

    public function mainOnly(): bool
    {
        return $this->boolean('main_only');
    }

    public function parentId(): ?int
    {
        return $this->has('parent_id') ? (int) $this->validated('parent_id') : null;
    }

    public function search(): ?string
    {
        return filled($this->validated('search')) ? trim($this->validated('search')) : null;
    }
}
