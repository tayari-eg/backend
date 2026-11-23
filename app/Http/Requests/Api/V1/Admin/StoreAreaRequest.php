<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Catalog\Models\Area;
use App\Http\Requests\Concerns\ValidatesAreaFields;
use App\Http\Requests\Concerns\ValidatesUniqueTranslatedNames;
use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
{
    use ValidatesAreaFields, ValidatesUniqueTranslatedNames;

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
            ...$this->uniqueTranslatedNameRules('required', Area::class, 'admin.area_name_taken'),
            ...$this->areaFieldRules('required'),
        ];
    }
}
