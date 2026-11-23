<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Wallet\Models\WalletPosting;
use App\Domains\Wallet\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A posting staff recorded (a settlement, an adjustment or a reversal) with every wallet line it wrote.
 *
 * @mixin WalletPosting
 */
class WalletPostingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...self::summary($this->resource),
            'transactions' => $this->transactions
                ->map(fn (WalletTransaction $transaction): array => [
                    'id' => $transaction->id,
                    'wallet_id' => $transaction->wallet_id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'balance_after' => $transaction->balance_after,
                    'available_at' => $transaction->available_at?->toIso8601ZuluString(),
                ])
                ->all(),
        ];
    }

    /**
     * The posting without its lines; reads the reason, creator and reversal relations.
     *
     * @return array<string, mixed>
     */
    public static function summary(WalletPosting $posting): array
    {
        return [
            'id' => $posting->id,
            'type' => $posting->type,
            'reference' => $posting->reference,
            'note' => $posting->note,
            'reason' => $posting->reason === null ? null : [
                'id' => $posting->reason->id,
                'name' => collect(Locale::values())
                    ->mapWithKeys(fn (string $locale): array => [$locale => $posting->reason->getTranslation('name', $locale, false)])
                    ->all(),
            ],
            'created_by' => $posting->creator === null ? null : ['id' => $posting->creator->id, 'name' => $posting->creator->name],
            'reversal_of_id' => $posting->reversal_of_id,
            'reversed_by_id' => $posting->reversal?->id,
            'can_reverse' => $posting->type->canBeReversed() && $posting->reversal === null,
            'created_at' => $posting->created_at?->toIso8601ZuluString(),
        ];
    }
}
