<?php

namespace App\Domains\Accounts\Models;

use App\Domains\Accounts\Enums\Locale;
use Database\Factories\BusinessTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

#[Fillable(['name', 'is_active', 'sort_order'])]
#[UseFactory(BusinessTypeFactory::class)]
class BusinessType extends Model
{
    /** @use HasFactory<BusinessTypeFactory> */
    use HasFactory, HasTranslations;

    /**
     * @var list<string>
     */
    public array $translatable = ['name'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<BusinessProfile, $this>
     */
    public function businessProfiles(): HasMany
    {
        return $this->hasMany(BusinessProfile::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Matches the whole name in one language, ignoring letter case.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function whereNameIs(Builder $query, string $locale, string $name): void
    {
        // Same reason as whereNameLike(): MySQL compares extracted JSON text case-sensitively.
        $query->whereRaw('lower('.$query->getQuery()->getGrammar()->wrap("name->{$locale}").') = ?', [mb_strtolower($name)]);
    }

    /**
     * Matches part of the name in any language, ignoring letter case.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function whereNameLike(Builder $query, string $search): void
    {
        // MySQL compares extracted JSON text case-sensitively, so lower both sides; the grammar writes the JSON path for each engine.
        $grammar = $query->getQuery()->getGrammar();
        $pattern = '%'.mb_strtolower($search).'%';

        $query->where(function (Builder $query) use ($grammar, $pattern): void {
            foreach (Locale::values() as $locale) {
                $query->orWhereRaw('lower('.$grammar->wrap("name->{$locale}").') like ?', [$pattern]);
            }
        });
    }
}
