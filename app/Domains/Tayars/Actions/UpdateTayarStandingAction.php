<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Models\TayarProfile;

class UpdateTayarStandingAction
{
    /**
     * Saves the rank and 30-day numbers the nightly job worked out, or only the starting rank on approval. Call it on the
     * locked profile, inside the caller's transaction.
     *
     * @param  array{rank_id?: ?int, rank_below_since?: mixed, rating_avg?: ?string, ratings_count_30d?: int, delivered_orders_30d?: int, standing_updated_at?: mixed}  $standing
     */
    public function handle(TayarProfile $profile, array $standing): TayarProfile
    {
        $profile->forceFill($standing)->save();

        return $profile;
    }
}
