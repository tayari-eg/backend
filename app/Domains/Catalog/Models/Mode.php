<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Models\TayarWorkSession;
use App\Models\Concerns\HasTranslatedNameSearch;
use Database\Factories\ModeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A way a tayar works (long distance, zone, area). The customer never picks one.
 */
#[Fillable(['code', 'name', 'description', 'allows_food', 'is_active', 'sort_order'])]
#[UseFactory(ModeFactory::class)]
class Mode extends Model
{
    /** @use HasFactory<ModeFactory> */
    use HasFactory, HasTranslatedNameSearch, HasTranslations;

    /**
     * @var list<string>
     */
    public array $translatable = ['name', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => ModeCode::class,
            'allows_food' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<TayarWorkSession, $this>
     */
    public function workSessions(): HasMany
    {
        return $this->hasMany(TayarWorkSession::class);
    }

    /**
     * @return HasMany<PricingRule, $this>
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
