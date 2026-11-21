<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Models\BusinessType;

class CreateBusinessTypeAction
{
    /**
     * A type without a sort order goes to the end of the list.
     *
     * @param  array{ar: string, en: string}  $name
     */
    public function handle(array $name, bool $isActive, ?int $sortOrder): BusinessType
    {
        return BusinessType::query()->create([
            'name' => $name,
            'is_active' => $isActive,
            'sort_order' => $sortOrder ?? (int) BusinessType::query()->max('sort_order') + 1,
        ]);
    }
}
