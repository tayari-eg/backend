<?php

namespace App\Jobs;

use App\Domains\Accounts\Actions\RecalculateBusinessRatingsAction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One chunk of the nightly business averages, with the same $now as the rank chunks of the run. Safe to run again.
 */
class RecalculateBusinessRatingsChunk implements ShouldQueue
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

    public function handle(RecalculateBusinessRatingsAction $recalculateBusinessRatings): void
    {
        $recalculateBusinessRatings->handle($this->profileIds, $this->now);
    }
}
