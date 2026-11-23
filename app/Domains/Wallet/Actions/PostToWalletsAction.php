<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Wallet\Data\PostingLine;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Events\WalletPosted;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletPosting;
use App\Domains\Wallet\Models\WalletTransaction;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The only way money moves: writes one posting and its transactions, and updates each wallet's cached balance, in one
 * transaction. The lines must add up to zero. The wallets are locked in id order (after any order, tayar profile and
 * offer rows the caller locked), then a posting with the same idempotency key is returned as it is, so a retry never
 * posts twice. $guard runs on the locked wallets before anything is written and may throw to refuse.
 */
class PostToWalletsAction
{
    /**
     * @param  list<PostingLine>  $lines
     * @param  array{created_by?: int|null, reason_id?: int|null, reference?: string|null, note?: string|null, reversal_of_id?: int|null}  $attributes
     * @param  (Closure(Collection<int, Wallet>): void)|null  $guard
     */
    public function handle(PostingType $type, string $idempotencyKey, array $lines, ?Model $source = null, array $attributes = [], ?Closure $guard = null): WalletPosting
    {
        $lines = array_values(array_filter($lines, fn (PostingLine $line): bool => ! $line->amount->isZero()));
        $total = array_reduce($lines, fn (BigDecimal $sum, PostingLine $line): BigDecimal => $sum->plus($line->amount), BigDecimal::zero());

        if ($lines === [] || ! $total->isZero()) {
            throw new LogicException("A {$type->value} posting must have lines that add up to zero.");
        }

        return DB::transaction(function () use ($type, $idempotencyKey, $lines, $source, $attributes, $guard): WalletPosting {
            $wallets = Wallet::query()
                ->whereKey(array_unique(array_map(fn (PostingLine $line): int => $line->wallet->id, $lines)))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $existing = WalletPosting::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing;
            }

            if ($guard !== null) {
                $guard($wallets);
            }

            $posting = WalletPosting::query()->create([
                ...$attributes,
                'idempotency_key' => $idempotencyKey,
                'type' => $type,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
            ]);

            $availableAt = self::payoutTime();

            foreach ($lines as $line) {
                $wallet = $wallets[$line->wallet->id];
                $balance = BigDecimal::of((string) $wallet->balance)->plus($line->amount)->toScale(2);

                WalletTransaction::query()->create([
                    'posting_id' => $posting->id,
                    'wallet_id' => $wallet->id,
                    'order_id' => $line->orderId,
                    'type' => $line->type,
                    'amount' => (string) $line->amount->toScale(2),
                    'balance_after' => (string) $balance,
                    'available_at' => $line->type->waitsForPayoutHour() ? $availableAt : now(),
                ]);

                $wallet->forceFill(['balance' => (string) $balance])->save();
            }

            WalletPosted::dispatch($posting);

            return $posting;
        });
    }

    /**
     * Today's payout hour in business time, or now when it has passed.
     */
    private static function payoutTime(): CarbonImmutable
    {
        $now = CarbonImmutable::now();
        $payoutHour = $now->timezone(config('app.business_timezone'))->setTime(config()->integer('tayari.wallet.available_from_hour'), 0);

        return $payoutHour->isAfter($now) ? $payoutHour->utc() : $now;
    }
}
