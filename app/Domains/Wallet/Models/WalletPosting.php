<?php

namespace App\Domains\Wallet\Models;

use App\Domains\Wallet\Enums\PostingType;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * One money event (a delivered order, a cancel fee, a bonus, a settlement, an adjustment or a reversal) with its
 * wallet transactions, which always add up to zero. Never updated or deleted.
 */
#[Fillable(['idempotency_key', 'type', 'source_type', 'source_id', 'reversal_of_id', 'created_by', 'reason_id', 'reference', 'note'])]
class WalletPosting extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Wallet postings are never updated.'));
        static::deleting(fn () => throw new LogicException('Wallet postings are never deleted.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PostingType::class,
            'source_id' => 'integer',
            'reversal_of_id' => 'integer',
            'created_by' => 'integer',
            'reason_id' => 'integer',
        ];
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'posting_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    /**
     * @return HasOne<self, $this>
     */
    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<WalletAdjustmentReason, $this>
     */
    public function reason(): BelongsTo
    {
        return $this->belongsTo(WalletAdjustmentReason::class, 'reason_id');
    }
}
