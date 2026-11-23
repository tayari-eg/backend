<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Area;

class DeactivateAreaAction
{
    /**
     * Tayars already working in it keep their session; only new go-online requests are refused.
     */
    public function handle(Area $area): Area
    {
        $area->forceFill(['is_active' => false])->save();

        return $area;
    }
}
