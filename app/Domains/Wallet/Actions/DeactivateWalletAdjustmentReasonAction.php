<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Wallet\Models\WalletAdjustmentReason;

class DeactivateWalletAdjustmentReasonAction
{
    /**
     * Past adjustments keep the reason; it can only no longer be picked.
     */
    public function handle(WalletAdjustmentReason $reason): WalletAdjustmentReason
    {
        $reason->forceFill(['is_active' => false])->save();

        return $reason;
    }
}
