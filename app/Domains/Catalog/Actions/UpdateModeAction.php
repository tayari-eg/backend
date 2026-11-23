<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Mode;

class UpdateModeAction
{
    /**
     * The code never changes: behaviour depends on it.
     *
     * @param  array{name?: array{ar: string, en: string}, description?: array{ar: string, en: string}, allows_food?: bool, is_active?: bool, sort_order?: int}  $attributes
     */
    public function handle(Mode $mode, array $attributes): Mode
    {
        $mode->update($attributes);

        return $mode;
    }
}
