<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Models\BusinessType;
use Closure;

trait ValidatesBusinessTypeNames
{
    /**
     * Both names are required, and each must be unique in its language ignoring letter case (and the type being updated).
     *
     * @return array<string, mixed>
     */
    protected function businessTypeNameRules(string $presence, ?int $ignoreId = null): array
    {
        $rules = ['name' => [$presence, 'array:'.implode(',', Locale::values())]];

        foreach (Locale::values() as $locale) {
            $rules["name.{$locale}"] = [
                'required_with:name',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($locale, $ignoreId): void {
                    $isTaken = is_string($value) && BusinessType::query()
                        ->whereNameIs($locale, $value)
                        ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                        ->exists();

                    if ($isTaken) {
                        $fail('admin.business_type_name_taken')->translate();
                    }
                },
            ];
        }

        return $rules;
    }
}
