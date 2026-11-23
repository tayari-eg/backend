<?php

namespace App\Console\Commands;

use App\Domains\Orders\Actions\SweepOrderOffersAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:dispatch-offers')]
#[Description('Expire overdue offers, offer every waiting order to the next round of tayars, and cancel orders no tayar accepted in time')]
class DispatchOrderOffers extends Command
{
    public function handle(SweepOrderOffersAction $sweepOrderOffers): int
    {
        $count = $sweepOrderOffers->handle();

        $this->info("{$count} offer(s) made.");

        return self::SUCCESS;
    }
}
