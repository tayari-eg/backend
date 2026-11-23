<?php

namespace Tests\Feature\Console;

use App\Contracts\PushSender;
use App\Domains\Accounts\Models\UserDevice;
use App\Exceptions\InvalidPushTokenException;
use App\Jobs\SendPushToDevice;
use App\Models\User;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class SendTestPushCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.firebase.credentials' => null]);
    }

    public function test_it_queues_a_test_push_for_a_user_found_by_id(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        UserDevice::factory()->count(2)->for($user)->create();

        $this->artisan('push:test', ['user' => (string) $user->id])
            ->expectsOutputToContain("Queued 2 push(es) for user {$user->id}")
            ->assertSuccessful();

        Queue::assertPushed(SendPushToDevice::class, fn (SendPushToDevice $job): bool => $job->data === ['type' => 'test']);
        Queue::assertPushed(SendPushToDevice::class, 2);
    }

    public function test_it_finds_the_user_by_phone_in_local_format(): void
    {
        Queue::fake();
        $user = User::factory()->create(['phone' => '+201012345678']);
        UserDevice::factory()->for($user)->create();

        $this->artisan('push:test', ['user' => '01012345678'])
            ->expectsOutputToContain("Queued 1 push(es) for user {$user->id}")
            ->assertSuccessful();
    }

    public function test_an_unknown_user_fails(): void
    {
        $this->artisan('push:test', ['user' => '999999'])
            ->expectsOutputToContain('No user found for [999999].')
            ->assertFailed();
    }

    public function test_a_user_without_devices_gets_a_warning(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->artisan('push:test', ['user' => (string) $user->id])
            ->expectsOutputToContain("User {$user->id} has no devices")
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_missing_credentials_are_reported(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        UserDevice::factory()->for($user)->create();

        $this->artisan('push:test', ['user' => (string) $user->id])
            ->expectsOutputToContain('Pushes are only logged, not sent: FIREBASE_CREDENTIALS is empty')
            ->assertSuccessful();
    }

    public function test_a_missing_credentials_file_is_reported_without_its_path(): void
    {
        Queue::fake();
        config(['services.firebase.credentials' => '/nowhere/firebase-credentials.json']);
        $user = User::factory()->create();
        UserDevice::factory()->for($user)->create();

        $this->artisan('push:test', ['user' => (string) $user->id])
            ->expectsOutputToContain('Pushes are only logged, not sent: the FIREBASE_CREDENTIALS file is missing or unreadable')
            ->doesntExpectOutputToContain('/nowhere/firebase-credentials.json')
            ->assertSuccessful();
    }

    public function test_now_sends_right_away_and_prints_each_devices_result(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $sent = UserDevice::factory()->for($user)->create(['push_token' => 'good-token']);
        $dead = UserDevice::factory()->for($user)->create(['push_token' => 'dead-token']);
        $this->fakeSender(['dead-token' => new InvalidPushTokenException('UNREGISTERED')]);

        $this->artisan('push:test', ['user' => (string) $user->id, '--now' => true])
            ->expectsOutputToContain("Device {$sent->id} (android): sent")
            ->expectsOutputToContain("Device {$dead->id} (android): dead token removed")
            ->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertModelExists($sent);
        $this->assertModelMissing($dead);
    }

    public function test_now_prints_an_error_and_fails_when_a_push_cannot_be_sent(): void
    {
        $user = User::factory()->create();
        $device = UserDevice::factory()->for($user)->create(['push_token' => 'broken-token']);
        $this->fakeSender(['broken-token' => new RuntimeException('FCM is down')]);

        $this->artisan('push:test', ['user' => (string) $user->id, '--now' => true])
            ->expectsOutputToContain("Device {$device->id} (android): error, FCM is down")
            ->assertFailed();

        $this->assertModelExists($device);
    }

    public function test_now_explains_a_sender_id_mismatch_and_keeps_the_device(): void
    {
        $user = User::factory()->create();
        $device = UserDevice::factory()->for($user)->create(['push_token' => 'other-project-token']);
        $this->fakeSender(['other-project-token' => new RequestException(new Response(new Psr7Response(403, [], json_encode(['error' => [
            'code' => 403,
            'message' => 'SenderId mismatch',
            'status' => 'PERMISSION_DENIED',
            'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'SENDER_ID_MISMATCH']],
        ]]))))]);

        $this->artisan('push:test', ['user' => (string) $user->id, '--now' => true])
            ->expectsOutputToContain('The credentials may belong to a different Firebase project than the app.')
            ->assertFailed();

        $this->assertModelExists($device);
    }

    /**
     * @param  array<string, Throwable>  $failuresByToken
     */
    private function fakeSender(array $failuresByToken): void
    {
        $this->app->instance(PushSender::class, new class($failuresByToken) implements PushSender
        {
            /**
             * @param  array<string, Throwable>  $failuresByToken
             */
            public function __construct(private readonly array $failuresByToken) {}

            public function send(UserDevice $device, string $title, string $body, array $data): void
            {
                if (isset($this->failuresByToken[$device->push_token])) {
                    throw $this->failuresByToken[$device->push_token];
                }
            }
        });
    }
}
