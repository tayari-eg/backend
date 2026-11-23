<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Area;

class ActivateAreaAction
{
    public function handle(Area $area): Area
    {
        $area->forceFill(['is_active' => true])->save();

        return $area;
    }
}
