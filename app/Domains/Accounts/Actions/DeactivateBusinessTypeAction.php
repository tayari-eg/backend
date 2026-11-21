<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Models\BusinessType;

class DeactivateBusinessTypeAction
{
    /**
     * Existing business profiles keep the type; it only disappears from the public list.
     */
    public function handle(BusinessType $businessType): BusinessType
    {
        $businessType->forceFill(['is_active' => false])->save();

        return $businessType;
    }
}
