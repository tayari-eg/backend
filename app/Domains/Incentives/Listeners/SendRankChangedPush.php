<?php

namespace App\Domains\Incentives\Listeners;

use App\Domains\Accounts\Actions\SendPushNotificationAction;
use App\Domains\Incentives\Enums\RankPushType;
use App\Domains\Incentives\Events\TayarRankChanged;

class SendRankChangedPush
{
    public function __construct(private SendPushNotificationAction $sendPush) {}

    /**
     * The tayar hears of the new rank, by name in the tayar's language, with up or down in the data.
     */
    public function handle(TayarRankChanged $event): void
    {
        $tayar = $event->profile->loadMissing('user')->user;
        $type = RankPushType::RankChanged;

        $this->sendPush->handle(
            $tayar,
            $type->value,
            $type->titleKey(),
            $event->isUp() ? 'push.rank_changed.up_body' : 'push.rank_changed.down_body',
            ['rank' => $event->to->getTranslation('name', $tayar->locale->value)],
            ['rank_code' => $event->to->code, 'direction' => $event->isUp() ? 'up' : 'down'],
        );
    }
}
