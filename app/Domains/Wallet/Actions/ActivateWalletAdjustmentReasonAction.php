<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Wallet\Models\WalletAdjustmentReason;

class ActivateWalletAdjustmentReasonAction
{
    public function handle(WalletAdjustmentReason $reason): WalletAdjustmentReason
    {
        $reason->forceFill(['is_active' => true])->save();

        return $reason;
    }
}
