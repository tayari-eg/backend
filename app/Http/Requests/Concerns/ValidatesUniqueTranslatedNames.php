<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Accounts\Enums\Locale;
use App\Models\Concerns\HasTranslatedNameSearch;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait ValidatesUniqueTranslatedNames
{
    /**
     * Both names are required, and each must be unique in its language ignoring letter case (and the row being updated).
     * $scope narrows the rows compared, e.g. to the siblings of a subcategory.
     *
     * @param  class-string<Model>  $modelClass  a model using HasTranslatedNameSearch
     * @param  (Closure(Builder<Model>): mixed)|null  $scope
     * @return array<string, mixed>
     */
    protected function uniqueTranslatedNameRules(string $presence, string $modelClass, string $takenMessageKey, ?int $ignoreId = null, ?Closure $scope = null): array
    {
        $rules = ['name' => [$presence, 'array:'.implode(',', Locale::values())]];

        foreach (Locale::values() as $locale) {
            $rules["name.{$locale}"] = [
                'required_with:name',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($modelClass, $locale, $ignoreId, $takenMessageKey, $scope): void {
                    $isTaken = is_string($value) && $modelClass::query()
                        ->whereNameIs($locale, $value)
                        ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                        ->when($scope !== null, $scope)
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
