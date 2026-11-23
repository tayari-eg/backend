<?php

namespace App\Jobs;

use App\Contracts\SmsSender;
use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\Locale;
use App\Domains\Orders\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Makes the receiver's delivery and refusal codes and sends them with the tracking link in one SMS. Only the order id is
 * queued: the plain codes are made here, never sit in the jobs table, and only their hashes are stored. Each run makes
 * new codes (a retry too), replacing the old ones and starting the wrong-code count again.
 */
class SendReceiverCodes implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60];

    public function __construct(public int $orderId) {}

    public function handle(SmsSender $smsSender): void
    {
        $codes = DB::transaction(function (): ?array {
            $order = Order::query()->lockForUpdate()->find($this->orderId);

            if ($order === null || ! $order->status->hasReceiverCodes()) {
                return null;
            }

            $delivery = self::newCode();

            do {
                $refusal = self::newCode();
            } while ($refusal === $delivery);

            $order->forceFill([
                'delivery_code_hash' => Hash::make($delivery),
                'refusal_code_hash' => Hash::make($refusal),
                'code_attempts' => 0,
                'codes_sent_at' => now(),
                'codes_expire_at' => now()->addHours(config()->integer('tayari.tracking.codes_valid_hours')),
            ])->save();

            return ['order' => $order, 'delivery' => $delivery, 'refusal' => $refusal];
        });

        if ($codes === null) {
            return;
        }

        /** @var Order $order */
        $order = $codes['order']->load(['sender.businessProfile', 'receiverUser']);
        $locale = $order->receiverUser?->locale->value ?? Locale::Ar->value;

        $smsSender->send(PhoneNumber::from($order->receiver_phone), __('orders.receiver_codes_sms', [
            'code' => $order->code,
            'sender' => $order->sender->businessProfile?->business_name ?? $order->sender->name,
            'delivery' => $codes['delivery'],
            'refusal' => $codes['refusal'],
            'url' => $order->trackingUrl(),
        ], $locale));
    }

    private static function newCode(): string
    {
        $length = config()->integer('tayari.tracking.code_length');

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
