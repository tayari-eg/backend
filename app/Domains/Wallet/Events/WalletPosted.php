<?php

namespace App\Domains\Wallet\Events;

use App\Domains\Wallet\Models\WalletPosting;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new posting was written (not a retry that returned an existing one).
 */
class WalletPosted implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public WalletPosting $posting) {}
}
