<?php

namespace App\Console\Commands;

use App\Domains\Incentives\Actions\DispatchStandingsRecalculationAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tayars:recalculate-ranks {--now : Run in this process instead of queueing it}')]
#[Description('Recalculate every tayar\'s rank and 30-day numbers, and every business\'s average rating, in chunked jobs')]
class RecalculateTayarRanks extends Command
{
    public function handle(DispatchStandingsRecalculationAction $dispatchRecalculation): int
    {
        $runNow = (bool) $this->option('now');
        $jobs = $dispatchRecalculation->handle(now(), $runNow);

        $this->info($runNow ? "Ranks and ratings recalculated in {$jobs} chunk(s)." : "{$jobs} rank recalculation job(s) queued.");

        return self::SUCCESS;
    }
}
