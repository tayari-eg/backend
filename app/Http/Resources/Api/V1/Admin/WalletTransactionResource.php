<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Wallet\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One line of a wallet as staff see it, with its posting's note, author and whether it can still be reversed.
 *
 * @mixin WalletTransaction
 */
class WalletTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'available_at' => $this->available_at?->toIso8601ZuluString(),
            'order' => $this->whenLoaded('order', fn (): ?array => $this->order === null ? null : [
                'id' => $this->order->id,
                'code' => $this->order->code,
            ]),
            'posting' => $this->whenLoaded('posting', fn (): array => WalletPostingResource::summary($this->posting)),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
