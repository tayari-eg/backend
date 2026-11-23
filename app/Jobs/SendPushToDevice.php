<?php

namespace App\Jobs;

use App\Contracts\PushSender;
use App\Domains\Accounts\Enums\PushDeliveryResult;
use App\Domains\Accounts\Models\UserDevice;
use App\Exceptions\InvalidPushTokenException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * One job per device, so a retry never pushes a second copy to the devices that already got it.
 */
class SendPushToDevice implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60];

    public bool $deleteWhenMissingModels = true;

    public int $userId;

    /**
     * Only the hash is queued, so the raw token never sits in the jobs table.
     */
    public string $pushTokenHash;

    /**
     * @param  array<string, string>  $data
     */
    public function __construct(
        public UserDevice $device,
        public string $title,
        public string $body,
        public array $data,
    ) {
        $this->userId = $device->user_id;
        $this->pushTokenHash = hash('sha256', $device->push_token);
    }

    public function handle(PushSender $pushSender): void
    {
        try {
            $this->deliver($pushSender);
        } catch (RequestException $exception) {
            if ($this->isRetryable($exception)) {
                throw $exception;
            }

            Log::error('Push not sent: FCM rejected the message', [
                'user_id' => $this->userId,
                'device_id' => $this->device->id,
                'status' => $exception->response->status(),
                'error' => $exception->response->json('error.message'),
            ]);

            $this->fail($exception);
        }
    }

    /**
     * The device is reloaded when the job runs: a token that moved to another user, or was replaced, is not pushed.
     *
     * @throws RequestException|ConnectionException
     */
    public function deliver(PushSender $pushSender): PushDeliveryResult
    {
        if ((int) $this->device->user_id !== $this->userId || ! hash_equals($this->pushTokenHash, hash('sha256', $this->device->push_token))) {
            Log::info('Push dropped: the device changed owner or token after it was queued', [
                'user_id' => $this->userId,
                'device_id' => $this->device->id,
            ]);

            return PushDeliveryResult::Dropped;
        }

        try {
            $pushSender->send($this->device, $this->title, $this->body, $this->data);
        } catch (InvalidPushTokenException $exception) {
            UserDevice::query()
                ->whereKey($this->device->id)
                ->where('user_id', $this->userId)
                ->where('push_token', $this->device->push_token)
                ->delete();

            Log::info('Push token removed: FCM reported it as dead', [
                'user_id' => $this->userId,
                'device_id' => $this->device->id,
                'error_code' => $exception->errorCode,
            ]);

            return PushDeliveryResult::TokenRemoved;
        }

        return PushDeliveryResult::Sent;
    }

    /**
     * A 401 is retried because the sender has already forgotten the expired access token.
     */
    private function isRetryable(RequestException $exception): bool
    {
        return in_array($exception->response->status(), [401, 429], true) || $exception->response->serverError();
    }
}
