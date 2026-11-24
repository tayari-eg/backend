<?php

namespace App\Jobs;

use App\Domains\Incentives\Actions\RecalculateTayarRanksAction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One chunk of the nightly rank run. Every chunk of a run gets the same $now, so all tayars share one window. Safe to
 * run again.
 */
class RecalculateTayarRanksChunk implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /**
     * Below the queue's retry_after (90 seconds), so a slow chunk is never picked up twice at once.
     */
    public int $timeout = 60;

    /**
     * @param  list<int>  $profileIds
     */
    public function __construct(public array $profileIds, public CarbonImmutable $now) {}

    public function handle(RecalculateTayarRanksAction $recalculateTayarRanks): void
    {
        $recalculateTayarRanks->handle($this->profileIds, $this->now);
    }
}
