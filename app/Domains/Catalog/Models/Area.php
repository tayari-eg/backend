<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Tayars\Models\TayarWorkSession;
use App\Models\Concerns\HasTranslatedNameSearch;
use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A district for the area mode, as a centre point and a radius.
 */
#[Fillable(['name', 'city', 'center_lat', 'center_lng', 'radius_km', 'is_active', 'sort_order'])]
#[UseFactory(AreaFactory::class)]
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory, HasTranslatedNameSearch, HasTranslations;

    public const string MIN_RADIUS_KM = '0.5';

    public const string MAX_RADIUS_KM = '50';

    /**
     * @var list<string>
     */
    public array $translatable = ['name', 'city'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'center_lat' => 'decimal:7',
            'center_lng' => 'decimal:7',
            'radius_km' => 'decimal:2',
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
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
