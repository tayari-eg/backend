<?php

namespace App\Domains\Incentives\Listeners;

use App\Domains\Accounts\Actions\SendPushNotificationAction;
use App\Domains\Incentives\Enums\RankPushType;
use App\Domains\Incentives\Events\TayarRankAtRisk;

class SendRankAtRiskPush
{
    public function __construct(private SendPushNotificationAction $sendPush) {}

    /**
     * The tayar hears once, when the grace days start, which rank is at risk and the business-time date it drops.
     */
    public function handle(TayarRankAtRisk $event): void
    {
        $tayar = $event->profile->loadMissing('user')->user;
        $type = RankPushType::RankAtRisk;

        $this->sendPush->handle(
            $tayar,
            $type->value,
            $type->titleKey(),
            $type->bodyKey(),
            [
                'rank' => $event->rank->getTranslation('name', $tayar->locale->value),
                'date' => $event->dropsAt->timezone(config('app.business_timezone'))->format('Y-m-d'),
            ],
            ['rank_code' => $event->rank->code, 'drops_at' => $event->dropsAt->toIso8601ZuluString()],
        );
    }
}
