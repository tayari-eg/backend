<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Accounts\Enums\Locale;
use App\Models\Concerns\HasTranslatedNameSearch;
use Closure;
use Illuminate\Database\Eloquent\Model;

trait ValidatesUniqueTranslatedNames
{
    /**
     * Both names are required, and each must be unique in its language ignoring letter case (and the row being updated).
     *
     * @param  class-string<Model>  $modelClass  a model using HasTranslatedNameSearch
     * @return array<string, mixed>
     */
    protected function uniqueTranslatedNameRules(string $presence, string $modelClass, string $takenMessageKey, ?int $ignoreId = null): array
    {
        $rules = ['name' => [$presence, 'array:'.implode(',', Locale::values())]];

        foreach (Locale::values() as $locale) {
            $rules["name.{$locale}"] = [
                'required_with:name',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($modelClass, $locale, $ignoreId, $takenMessageKey): void {
                    $isTaken = is_string($value) && $modelClass::query()
                        ->whereNameIs($locale, $value)
                        ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                        ->exists();

                    if ($isTaken) {
                        $fail($takenMessageKey)->translate();
                    }
                },
            ];
        }

        return $rules;
    }
}
