<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Wallet\Data\WalletSummary;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The owner's wallet. balance: what Tayari owes the owner (negative: what the owner owes Tayari). Amounts are decimal
 * strings with two decimals (EGP).
 *
 * @mixin Wallet
 */
class WalletResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = WalletSummary::of($this->resource);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'balance' => (string) $summary->balance->toScale(2),
            // Without the earnings and bonuses still waiting for today's payout hour.
            'available_balance' => (string) $summary->available->toScale(2),
            // What Tayari can pay out now.
            'payable_balance' => (string) $summary->payable->toScale(2),
            $this->mergeWhen($this->type === WalletType::Tayar, fn (): array => [
                'locked_bonus' => (string) $summary->lockedBonus->toScale(2),
                'bonus_unlock_delivered_orders' => config()->integer('tayari.wallet.bonus_unlock_delivered_orders'),
                'cash_to_hand_in' => (string) $summary->cashToHandIn->toScale(2),
                'max_cash_owed' => (string) config('tayari.wallet.max_cash_owed'),
                'blocked_from_offers' => $summary->blockedFromOffers,
            ]),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
