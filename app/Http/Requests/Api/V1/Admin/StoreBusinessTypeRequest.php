<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesBusinessTypeNames;
use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessTypeRequest extends FormRequest
{
    use ValidatesBusinessTypeNames;

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
            ...$this->businessTypeNameRules('required'),
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
