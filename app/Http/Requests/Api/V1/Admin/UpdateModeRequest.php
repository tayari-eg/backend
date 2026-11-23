<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Catalog\Models\Mode;
use App\Http\Requests\Concerns\ValidatesUniqueTranslatedNames;
use Illuminate\Foundation\Http\FormRequest;

class UpdateModeRequest extends FormRequest
{
    use ValidatesUniqueTranslatedNames;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Send only the fields that change. The code can't change; name and description need both languages.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            ...$this->uniqueTranslatedNameRules('sometimes', Mode::class, 'admin.mode_name_taken', $this->route('mode')?->id),
            'description' => ['sometimes', 'array:'.implode(',', Locale::values())],
            'allows_food' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];

        foreach (Locale::values() as $locale) {
            $rules["description.{$locale}"] = ['required_with:description', 'string', 'max:500'];
        }

        return $rules;
    }
}
