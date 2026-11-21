<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\PaginatesResults;
use Illuminate\Foundation\Http\FormRequest;

class ListBusinessTypesRequest extends FormRequest
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
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            ...$this->paginationRules(),
        ];
    }

    public function isActive(): ?bool
    {
        return $this->has('is_active') ? $this->boolean('is_active') : null;
    }

    public function search(): ?string
    {
        return filled($this->validated('search')) ? trim($this->validated('search')) : null;
    }
}
