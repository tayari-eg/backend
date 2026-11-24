<?php

namespace App\Domains\Incentives\Actions;

use App\Domains\Incentives\Enums\RankChangeSource;
use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Incentives\Models\TayarRankHistory;
use App\Domains\Tayars\Actions\UpdateTayarStandingAction;
use App\Domains\Tayars\Models\TayarProfile;

class AssignStartingRankAction
{
    public function __construct(private UpdateTayarStandingAction $updateStanding) {}

    /**
     * Gives a tayar without a rank the lowest rank, with a history row from no rank. A tayar who already has one (approved
     * again after a review) keeps it. Call it on the locked profile, inside the caller's transaction.
     */
    public function handle(TayarProfile $profile): TayarProfile
    {
        $starting = TayarRank::ladder()->first();

        if ($profile->rank_id !== null || $starting === null) {
            return $profile;
        }

        $this->updateStanding->handle($profile, ['rank_id' => $starting->id]);

        TayarRankHistory::query()->create([
            'tayar_profile_id' => $profile->id,
            'from_rank_id' => null,
            'to_rank_id' => $starting->id,
            'source' => RankChangeSource::Auto,
        ]);

        return $profile;
    }
}
