<?php

namespace App\Domains\Wallet\Models;

use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\WalletType;
use App\Models\User;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A tayar's or a business's wallet, or one of Tayari's system wallets. The balance is what Tayari owes the owner
 * (negative: what the owner owes Tayari). Only PostToWalletsAction changes it, together with its transactions.
 */
#[Fillable(['user_id', 'type', 'system_code'])]
#[UseFactory(WalletFactory::class)]
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'type' => WalletType::class,
            'system_code' => SystemWallet::class,
            'balance' => 'decimal:2',
        ];
    }

    /**
     * The user's wallet of this type, created on first use. insertOrIgnore makes two requests creating the same wallet
     * safe only outside a transaction: inside one, MySQL's REPEATABLE READ can hide the row the other request committed,
     * and the read after the ignored insert fails. Posting actions create their wallets first (EnsureWalletsAction), so
     * inside their transactions this only reads.
     */
    public static function ensureFor(int $userId, WalletType $type): self
    {
        $wallet = self::query()->where('user_id', $userId)->where('type', $type)->first();

        if ($wallet !== null) {
            return $wallet;
        }

        self::query()->insertOrIgnore(['user_id' => $userId, 'type' => $type->value, 'balance' => '0.00', 'created_at' => now(), 'updated_at' => now()]);

        return self::query()->where('user_id', $userId)->where('type', $type)->firstOrFail();
    }

    /**
     * One of Tayari's system wallets (SystemWalletSeeder creates them all), created on first use with the same rule as
     * ensureFor().
     */
    public static function system(SystemWallet $code): self
    {
        $wallet = self::query()->where('system_code', $code)->first();

        if ($wallet !== null) {
            return $wallet;
        }

        self::query()->insertOrIgnore(['type' => WalletType::System->value, 'system_code' => $code->value, 'balance' => '0.00', 'created_at' => now(), 'updated_at' => now()]);

        return self::query()->where('system_code', $code)->firstOrFail();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function isSystem(): bool
    {
        return $this->type === WalletType::System;
    }
}
