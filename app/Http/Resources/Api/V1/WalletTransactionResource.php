<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Wallet\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One line of the owner's wallet. amount is signed: positive is money Tayari owes the owner, negative is money taken or
 * owed back. Staff notes are never shown here.
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
            'posting' => $this->whenLoaded('posting', fn (): array => [
                'id' => $this->posting->id,
                'type' => $this->posting->type,
                'reference' => $this->posting->reference,
                'reason' => $this->posting->reason?->name,
                'reversal_of_id' => $this->posting->reversal_of_id,
            ]),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
