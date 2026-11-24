<?php

namespace App\Domains\Incentives\Events;

use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Tayars\Models\TayarProfile;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A tayar fell below the current rank's requirements; the rank drops at $dropsAt unless the tayar meets them again.
 */
class TayarRankAtRisk implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public TayarProfile $profile, public TayarRank $rank, public CarbonImmutable $dropsAt) {}
}
