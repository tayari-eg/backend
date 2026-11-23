<?php

namespace App\Domains\Wallet\Models;

use App\Models\Concerns\HasTranslatedNameSearch;
use Database\Factories\WalletAdjustmentReasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A reason staff pick for a manual adjustment or a reversal.
 */
#[Fillable(['name', 'is_active', 'sort_order'])]
#[UseFactory(WalletAdjustmentReasonFactory::class)]
class WalletAdjustmentReason extends Model
{
    /** @use HasFactory<WalletAdjustmentReasonFactory> */
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
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<WalletPosting, $this>
     */
    public function postings(): HasMany
    {
        return $this->hasMany(WalletPosting::class, 'reason_id');
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
