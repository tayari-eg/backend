<?php

namespace App\Domains\Incentives\Actions;

use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Tayars\Models\TayarProfile;
use App\Jobs\RecalculateBusinessRatingsChunk;
use App\Jobs\RecalculateTayarRanksChunk;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class DispatchStandingsRecalculationAction
{
    /**
     * Tayars or businesses per job, so no single job handles the whole fleet.
     */
    private const int CHUNK_SIZE = 100;

    /**
     * Splits the nightly run into one job per chunk of approved tayars, then per chunk of businesses, all with the same
     * $now. With $now false they are queued; with true they run in this process. Returns how many jobs it made.
     */
    public function handle(CarbonInterface $now, bool $runNow = false): int
    {
        $now = CarbonImmutable::instance($now);
        $jobs = [];

        TayarProfile::query()->approved()->select('id')->chunkById(self::CHUNK_SIZE, function ($profiles) use ($now, &$jobs): void {
            $jobs[] = new RecalculateTayarRanksChunk($profiles->modelKeys(), $now);
        });

        BusinessProfile::query()->select('id')->chunkById(self::CHUNK_SIZE, function ($profiles) use ($now, &$jobs): void {
            $jobs[] = new RecalculateBusinessRatingsChunk($profiles->modelKeys(), $now);
        });

        foreach ($jobs as $job) {
            $runNow ? dispatch_sync($job) : dispatch($job);
        }

        return count($jobs);
    }
}
