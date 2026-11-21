<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Models\BusinessType;

class UpdateBusinessTypeAction
{
    /**
     * @param  array{name?: array{ar: string, en: string}, is_active?: bool, sort_order?: int}  $attributes
     */
    public function handle(BusinessType $businessType, array $attributes): BusinessType
    {
        $businessType->update($attributes);

        return $businessType;
    }
}
