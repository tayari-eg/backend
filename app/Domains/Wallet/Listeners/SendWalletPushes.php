<?php

namespace App\Domains\Wallet\Listeners;

use App\Domains\Accounts\Actions\SendPushNotificationAction;
use App\Domains\Wallet\Enums\WalletPushType;
use App\Domains\Wallet\Events\WalletPosted;
use App\Domains\Wallet\Models\WalletTransaction;

class SendWalletPushes
{
    public function __construct(private SendPushNotificationAction $sendPush) {}

    /**
     * Each user wallet in a settlement, adjustment, reversal or signup bonus posting hears about it with its own amount.
     */
    public function handle(WalletPosted $event): void
    {
        $type = WalletPushType::forPosting($event->posting->type);

        if ($type === null) {
            return;
        }

        $event->posting->transactions()
            ->with('wallet.user')
            ->get()
            ->filter(fn (WalletTransaction $transaction): bool => $transaction->wallet->user !== null)
            ->each(fn (WalletTransaction $transaction) => $this->sendPush->handle(
                $transaction->wallet->user,
                $type->value,
                $type->titleKey(),
                $type->bodyKey(),
                ['amount' => ltrim((string) $transaction->amount, '-')],
                ['wallet_id' => $transaction->wallet_id, 'posting_id' => $event->posting->id],
            ));
    }
}
