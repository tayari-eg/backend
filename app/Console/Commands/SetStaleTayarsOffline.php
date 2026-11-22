<?php

namespace App\Console\Commands;

use App\Domains\Tayars\Actions\SetStaleTayarsOfflineAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tayars:set-stale-offline')]
#[Description('Set offline the tayars that sent no location for the configured minutes, and close their work sessions')]
class SetStaleTayarsOffline extends Command
{
    public function handle(SetStaleTayarsOfflineAction $setStaleTayarsOffline): int
    {
        $count = $setStaleTayarsOffline->handle();

        $this->info("{$count} tayar(s) set offline.");

        return self::SUCCESS;
    }
}
