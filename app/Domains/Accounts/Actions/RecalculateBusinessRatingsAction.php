<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Incentives\Data\StandingWindow;
use App\Domains\Orders\Enums\RaterRole;
use App\Domains\Orders\Models\OrderRating;
use Carbon\CarbonInterface;

class RecalculateBusinessRatingsAction
{
    /**
     * Saves each given business's average from its tayars' ratings revealed in the same window as the tayar ranks
     * (StandingWindow), for staff only. Safe to run again. Returns how many profiles it updated.
     *
     * @param  list<int>  $profileIds
     */
    public function handle(array $profileIds, CarbonInterface $now): int
    {
        $window = StandingWindow::before($now);
        $profiles = BusinessProfile::query()->whereKey($profileIds)->orderBy('id')->get();

        foreach ($profiles as $profile) {
            $rating = OrderRating::averageFor($profile->user_id, RaterRole::Tayar, $window->since, $window->until);

            $profile->forceFill([
                'rating_avg' => $rating['average'],
                'ratings_count_30d' => $rating['count'],
                'rating_updated_at' => $now,
            ])->save();
        }

        return $profiles->count();
    }
}
