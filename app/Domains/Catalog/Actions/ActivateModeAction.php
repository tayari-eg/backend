<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Mode;

class ActivateModeAction
{
    public function handle(Mode $mode): Mode
    {
        $mode->forceFill(['is_active' => true])->save();

        return $mode;
    }
}
