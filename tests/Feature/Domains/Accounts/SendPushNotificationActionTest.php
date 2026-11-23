<?php

namespace Tests\Feature\Domains\Accounts;

use App\Domains\Accounts\Actions\SendPushNotificationAction;
use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Models\UserDevice;
use App\Jobs\SendPushToDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendPushNotificationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_one_job_per_device_of_the_user_only(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $devices = UserDevice::factory()->count(2)->for($user)->create();
        UserDevice::factory()->create();

        $jobs = app(SendPushNotificationAction::class)->handle($user, 'test', 'push.test.title', 'push.test.body');

        $this->assertCount(2, $jobs);
        Queue::assertPushed(SendPushToDevice::class, 2);
        foreach ($devices as $device) {
            Queue::assertPushed(SendPushToDevice::class, fn (SendPushToDevice $job): bool => $job->device->is($device));
        }
    }

    public function test_the_title_and_body_use_the_users_locale(): void
    {
        Queue::fake();
        $arabicUser = User::factory()->create(['locale' => Locale::Ar]);
        $englishUser = User::factory()->create(['locale' => Locale::En]);
        UserDevice::factory()->for($arabicUser)->create();
        UserDevice::factory()->for($englishUser)->create();

        app(SendPushNotificationAction::class)->handle($arabicUser, 'test', 'push.test.title', 'push.test.body');
        app(SendPushNotificationAction::class)->handle($englishUser, 'test', 'push.test.title', 'push.test.body');

        Queue::assertPushed(SendPushToDevice::class, fn (SendPushToDevice $job): bool => $job->device->user_id === $arabicUser->id
            && $job->title === 'إشعار تجريبي من طياري'
            && $job->body === 'الإشعارات تعمل على هذا الجهاز.');
        Queue::assertPushed(SendPushToDevice::class, fn (SendPushToDevice $job): bool => $job->device->user_id === $englishUser->id
            && $job->title === 'Tayari test notification'
            && $job->body === 'Push notifications are working on this device.');
    }

    public function test_the_data_carries_the_type_and_every_value_is_a_string(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        UserDevice::factory()->for($user)->create();

        app(SendPushNotificationAction::class)->handle($user, 'test', 'push.test.title', 'push.test.body', data: ['order_id' => 15]);

        Queue::assertPushed(SendPushToDevice::class, fn (SendPushToDevice $job): bool => $job->data === ['type' => 'test', 'order_id' => '15']);
    }

    public function test_a_user_without_devices_queues_nothing(): void
    {
        Queue::fake();

        $jobs = app(SendPushNotificationAction::class)->handle(User::factory()->create(), 'test', 'push.test.title', 'push.test.body');

        $this->assertCount(0, $jobs);
        Queue::assertNothingPushed();
    }

    public function test_without_queueing_it_returns_the_jobs_and_queues_nothing(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        UserDevice::factory()->for($user)->create();

        $jobs = app(SendPushNotificationAction::class)->handle($user, 'test', 'push.test.title', 'push.test.body', queue: false);

        $this->assertCount(1, $jobs);
        Queue::assertNothingPushed();
    }
}
