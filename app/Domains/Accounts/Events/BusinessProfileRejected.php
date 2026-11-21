<?php

namespace App\Domains\Accounts\Events;

use App\Domains\Accounts\Models\BusinessProfile;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BusinessProfileRejected implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public BusinessProfile $profile) {}
}
