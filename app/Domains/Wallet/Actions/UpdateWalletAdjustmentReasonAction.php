<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Wallet\Models\WalletAdjustmentReason;

class UpdateWalletAdjustmentReasonAction
{
    /**
     * @param  array{name?: array{ar: string, en: string}, is_active?: bool, sort_order?: int}  $attributes
     */
    public function handle(WalletAdjustmentReason $reason, array $attributes): WalletAdjustmentReason
    {
        $reason->update($attributes);

        return $reason;
    }
}
