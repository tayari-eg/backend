<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Wallet\Data\WalletSummary;
use App\Domains\Wallet\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A wallet as staff see it: the owner (null for Tayari's system wallets) and the cached balance; one wallet also has
 * its payout summary. Amounts are decimal strings with two decimals (EGP).
 *
 * @mixin Wallet
 */
class WalletResource extends JsonResource
{
    private bool $withSummary = false;

    public static function detail(Wallet $wallet): self
    {
        $resource = new self($wallet);
        $resource->withSummary = true;

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'system_code' => $this->system_code,
            'owner' => $this->whenLoaded('user', fn (): ?array => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
            ]),
            'balance' => $this->balance,
            'summary' => $this->when($this->withSummary, function (): array {
                $summary = WalletSummary::of($this->resource);

                return [
                    'available_balance' => (string) $summary->available->toScale(2),
                    'payable_balance' => (string) $summary->payable->toScale(2),
                    'locked_bonus' => (string) $summary->lockedBonus->toScale(2),
                    'cash_to_hand_in' => (string) $summary->cashToHandIn->toScale(2),
                    'blocked_from_offers' => $summary->blockedFromOffers,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
