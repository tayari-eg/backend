<?php

namespace App\Console\Commands;

use App\Contracts\PushSender;
use App\Domains\Accounts\Actions\SendPushNotificationAction;
use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\PushDeliveryResult;
use App\Models\User;
use App\Services\Push\FcmPushSender;
use App\Services\Push\LogPushSender;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Throwable;

#[Signature('push:test {user : The user id or phone number} {--now : Send right away and print each device\'s result instead of queueing}')]
#[Description('Send a test push notification to every device of one user')]
class SendTestPush extends Command
{
    public function handle(SendPushNotificationAction $sendPushNotification, PushSender $pushSender): int
    {
        $user = $this->findUser((string) $this->argument('user'));

        if ($user === null) {
            $this->error("No user found for [{$this->argument('user')}].");

            return self::FAILURE;
        }

        $isLogOnly = $pushSender instanceof LogPushSender;

        if ($isLogOnly) {
            $this->warn("Pushes are only logged, not sent: {$pushSender->reason}. Check FIREBASE_CREDENTIALS.");
        }

        $sendNow = (bool) $this->option('now');
        $jobs = $sendPushNotification->handle($user, 'test', 'push.test.title', 'push.test.body', queue: ! $sendNow);

        if ($jobs->isEmpty()) {
            $this->warn("User {$user->id} has no devices: log in on the app first.");

            return self::SUCCESS;
        }

        if (! $sendNow) {
            $this->info("Queued {$jobs->count()} push(es) for user {$user->id}; they go out on the next schedule:run.");

            return self::SUCCESS;
        }

        $hasErrors = false;

        foreach ($jobs as $job) {
            $label = "Device {$job->device->id} ({$job->device->platform->value})";

            try {
                $result = $job->deliver($pushSender);
                $this->line($label.': '.($result === PushDeliveryResult::Sent && $isLogOnly ? 'skipped, only logged' : $result->label()));
            } catch (Throwable $exception) {
                $hasErrors = true;
                $this->error("{$label}: error, {$exception->getMessage()}".$this->hintFor($exception));
            }
        }

        return $hasErrors ? self::FAILURE : self::SUCCESS;
    }

    private function hintFor(Throwable $exception): string
    {
        $isSenderIdMismatch = $exception instanceof RequestException
            && FcmPushSender::errorCode($exception->response) === FcmPushSender::SENDER_ID_MISMATCH;

        return $isSenderIdMismatch ? ' The credentials may belong to a different Firebase project than the app.' : '';
    }

    private function findUser(string $idOrPhone): ?User
    {
        $phone = PhoneNumber::tryFrom($idOrPhone);

        if ($phone !== null) {
            return User::query()->where('phone', $phone->value)->first();
        }

        return ctype_digit($idOrPhone) ? User::query()->find($idOrPhone) : null;
    }
}
