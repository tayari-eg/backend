<?php

namespace App\Domains\Incentives\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The tayari.ranks.window_days whole business days before the day of a run: [since, until), both in UTC. A run at
 * 03:00 Cairo counts up to midnight, so orders delivered between midnight and the run wait for the next night.
 */
final readonly class StandingWindow
{
    public function __construct(public CarbonImmutable $since, public CarbonImmutable $until) {}

    public static function before(CarbonInterface $now): self
    {
        $today = CarbonImmutable::instance($now)->setTimezone(config('app.business_timezone'))->startOfDay();

        return new self(
            $today->subDays(config()->integer('tayari.ranks.window_days'))->startOfDay()->utc(),
            $today->utc(),
        );
    }
}
