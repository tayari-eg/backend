<?php

namespace App\Domains\Tayars\Events;

use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TayarApplicationApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public TayarProfile $profile) {}
}
