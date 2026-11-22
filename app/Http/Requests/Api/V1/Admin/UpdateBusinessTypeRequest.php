<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Accounts\Models\BusinessType;
use App\Http\Requests\Concerns\ValidatesUniqueTranslatedNames;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessTypeRequest extends FormRequest
{
    use ValidatesUniqueTranslatedNames;

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
            ...$this->uniqueTranslatedNameRules('sometimes', BusinessType::class, 'admin.business_type_name_taken', $this->route('businessType')?->id),
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
