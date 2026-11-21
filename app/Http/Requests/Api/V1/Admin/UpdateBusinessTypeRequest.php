<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesBusinessTypeNames;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessTypeRequest extends FormRequest
{
    use ValidatesBusinessTypeNames;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Send only the fields that change; a name always needs both languages.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->businessTypeNameRules('sometimes', $this->route('businessType')?->id),
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
