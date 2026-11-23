<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Mode;

class DeactivateModeAction
{
    /**
     * Tayars already working in it keep their session; only new go-online requests are refused.
     */
    public function handle(Mode $mode): Mode
    {
        $mode->forceFill(['is_active' => false])->save();

        return $mode;
    }
}
