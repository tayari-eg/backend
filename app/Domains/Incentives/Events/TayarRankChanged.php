<?php

namespace App\Domains\Incentives\Events;

use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The nightly job moved a tayar up, or down after the grace days. Not fired for the starting rank.
 */
class TayarRankChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public TayarProfile $profile, public TayarRank $from, public TayarRank $to) {}

    public function isUp(): bool
    {
        return $this->to->level > $this->from->level;
    }
}
