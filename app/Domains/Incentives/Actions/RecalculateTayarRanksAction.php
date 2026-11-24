<?php

namespace App\Domains\Incentives\Actions;

use App\Domains\Tayars\Models\TayarProfile;
use Carbon\CarbonInterface;

class RecalculateTayarRanksAction
{
    public function __construct(private RecalculateTayarRankAction $recalculateTayarRank) {}

    /**
     * Recalculates the rank of each approved tayar among the given profiles, one transaction each, all as of $now.
     * Returns how many tayars it checked.
     *
     * @param  list<int>  $profileIds
     */
    public function handle(array $profileIds, CarbonInterface $now): int
    {
        $profiles = TayarProfile::query()->approved()->whereKey($profileIds)->orderBy('id')->get();

        foreach ($profiles as $profile) {
            $this->recalculateTayarRank->handle($profile, $now);
        }

        return $profiles->count();
    }
}
