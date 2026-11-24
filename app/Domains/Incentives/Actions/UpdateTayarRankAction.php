<?php

namespace App\Domains\Incentives\Actions;

use App\Domains\Incentives\Models\TayarRank;

class UpdateTayarRankAction
{
    /**
     * Staff change only the name, the thresholds and the colour; the code and level stay. Tayars move at the next
     * nightly run.
     *
     * @param  array{name?: array{ar: string, en: string}, min_delivered_orders_30d?: int, min_rating?: string|null, color?: string}  $attributes
     */
    public function handle(TayarRank $rank, array $attributes): TayarRank
    {
        $rank->update($attributes);

        return $rank;
    }
}
