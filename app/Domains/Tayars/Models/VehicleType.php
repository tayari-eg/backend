<?php

namespace App\Domains\Tayars\Models;

use App\Domains\Pricing\Models\PricingRule;
use App\Models\Concerns\HasTranslatedNameSearch;
use Database\Factories\VehicleTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

#[Fillable(['name', 'requires_license', 'can_carry_food', 'is_active', 'sort_order'])]
#[UseFactory(VehicleTypeFactory::class)]
class VehicleType extends Model
{
    /** @use HasFactory<VehicleTypeFactory> */
    use HasFactory, HasTranslatedNameSearch, HasTranslations;

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
            'requires_license' => 'boolean',
            'can_carry_food' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
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
