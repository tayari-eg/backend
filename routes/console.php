<?php

use App\Domains\Accounts\Models\OtpCode;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Shared hosting has no long-running worker: one cron runs schedule:run, which drains the queue every minute.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->when(fn (): bool => config()->boolean('tayari.queue.work_from_scheduler', true));

Schedule::command('model:prune', ['--model' => [OtpCode::class]])->daily();

Schedule::command('tayars:set-stale-offline')->everyMinute()->withoutOverlapping(5);

// Offers expire within a minute; reads and accepts check expires_at themselves, this only starts the next round.
Schedule::command('orders:dispatch-offers')->everyMinute()->withoutOverlapping(5);

Schedule::command('tayars:recalculate-ranks')
    ->dailyAt(config('tayari.ranks.recalculate_at'))
    ->timezone(config('app.business_timezone'));
