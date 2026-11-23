<?php

namespace App\Domains\Accounts\Actions;

use App\Jobs\SendPushToDevice;
use App\Models\User;
use Illuminate\Support\Collection;

class SendPushNotificationAction
{
    /**
     * Sends to every device of the user, in the user's locale. The data always carries the type, and every value is a string.
     * With $queue false the jobs are returned without being queued, for the caller to run now (push:test --now).
     *
     * @param  array<string, string|int>  $replace
     * @param  array<string, string|int>  $data
     * @return Collection<int, SendPushToDevice>
     */
    public function handle(
        User $user,
        string $type,
        string $titleKey,
        string $bodyKey,
        array $replace = [],
        array $data = [],
        bool $queue = true,
    ): Collection {
        $locale = $user->locale->value;
        $title = __($titleKey, $replace, $locale);
        $body = __($bodyKey, $replace, $locale);
        $data = array_map(strval(...), ['type' => $type] + $data);

        $jobs = $user->devices()->get()
            ->map(fn ($device): SendPushToDevice => new SendPushToDevice($device, $title, $body, $data));

        if ($queue) {
            $jobs->each(fn (SendPushToDevice $job) => dispatch($job));
        }

        return $jobs;
    }
}
