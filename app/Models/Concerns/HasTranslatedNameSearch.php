<?php

namespace App\Models\Concerns;

use App\Domains\Accounts\Enums\Locale;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Case-insensitive matches on a translatable JSON `name` column. MySQL compares extracted JSON text
 * case-sensitively, so both sides are lowered; the grammar writes the JSON path for each engine.
 */
trait HasTranslatedNameSearch
{
    use EscapesLikeValues;

    /**
     * Matches the whole name in one language, ignoring letter case.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function whereNameIs(Builder $query, string $locale, string $name): void
    {
        $query->whereRaw('lower('.$query->getQuery()->getGrammar()->wrap("name->{$locale}").') = ?', [mb_strtolower($name)]);
    }

    /**
     * Matches part of the name in any language, ignoring letter case.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function whereNameLike(Builder $query, string $search): void
    {
        $grammar = $query->getQuery()->getGrammar();
        $pattern = static::likeContains(mb_strtolower($search));

        $query->where(function (Builder $query) use ($grammar, $pattern): void {
            foreach (Locale::values() as $locale) {
                $query->orWhereRaw('lower('.$grammar->wrap("name->{$locale}").') like ?', [$pattern]);
            }
        });
    }
}
