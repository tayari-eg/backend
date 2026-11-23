<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Wallet\Models\WalletAdjustmentReason;

class CreateWalletAdjustmentReasonAction
{
    /**
     * A reason without a sort order goes to the end of the list.
     *
     * @param  array{ar: string, en: string}  $name
     */
    public function handle(array $name, bool $isActive, ?int $sortOrder): WalletAdjustmentReason
    {
        return WalletAdjustmentReason::query()->create([
            'name' => $name,
            'is_active' => $isActive,
            'sort_order' => $sortOrder ?? (int) WalletAdjustmentReason::query()->max('sort_order') + 1,
        ]);
    }
}
