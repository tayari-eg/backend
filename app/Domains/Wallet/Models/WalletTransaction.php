<?php

namespace App\Domains\Wallet\Models;

use App\Domains\Orders\Models\Order;
use App\Domains\Wallet\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One line of the ledger: a signed amount on one wallet, with the balance after it. Never updated or deleted.
 */
#[Fillable(['posting_id', 'wallet_id', 'order_id', 'type', 'amount', 'balance_after', 'available_at'])]
class WalletTransaction extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Wallet transactions are never updated.'));
        static::deleting(fn () => throw new LogicException('Wallet transactions are never deleted.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posting_id' => 'integer',
            'wallet_id' => 'integer',
            'order_id' => 'integer',
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'available_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WalletPosting, $this>
     */
    public function posting(): BelongsTo
    {
        return $this->belongsTo(WalletPosting::class, 'posting_id');
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }
}
