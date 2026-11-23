<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Catalog\Models\Area;
use App\Http\Requests\Concerns\ValidatesAreaFields;
use App\Http\Requests\Concerns\ValidatesUniqueTranslatedNames;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAreaRequest extends FormRequest
{
    use ValidatesAreaFields, ValidatesUniqueTranslatedNames;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Send only the fields that change; name and city always need both languages.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->uniqueTranslatedNameRules('sometimes', Area::class, 'admin.area_name_taken', $this->route('area')?->id),
            ...$this->areaFieldRules('sometimes'),
        ];
    }
}
