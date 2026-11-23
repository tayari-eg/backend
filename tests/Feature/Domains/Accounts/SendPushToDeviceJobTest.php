<?php

namespace Tests\Feature\Domains\Accounts;

use App\Contracts\PushSender;
use App\Domains\Accounts\Actions\RegisterDeviceAction;
use App\Domains\Accounts\Enums\Platform;
use App\Domains\Accounts\Models\UserDevice;
use App\Exceptions\InvalidPushTokenException;
use App\Jobs\SendPushToDevice;
use App\Models\User;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;

class SendPushToDeviceJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sent_push_keeps_the_device(): void
    {
        $device = UserDevice::factory()->create();
        $sender = $this->fakeSender();

        $this->job($device)->handle($sender);

        $this->assertSame([[$device->push_token, 'Title', 'Body', ['type' => 'test']]], $sender->sent);
        $this->assertModelExists($device);
    }

    public function test_a_dead_token_removes_the_device_without_retrying(): void
    {
        Log::spy();
        $device = UserDevice::factory()->create();
        $job = $this->job($device)->withFakeQueueInteractions();

        $job->handle($this->fakeSender(new InvalidPushTokenException('UNREGISTERED')));

        $this->assertModelMissing($device);
        $job->assertNotFailed();
        Log::shouldHaveReceived('info')->once()->with('Push token removed: FCM reported it as dead', [
            'user_id' => $device->user_id,
            'device_id' => $device->id,
            'error_code' => 'UNREGISTERED',
        ]);
    }

    public function test_a_push_queued_before_the_device_moved_to_another_user_is_dropped(): void
    {
        Log::spy();
        $device = UserDevice::factory()->create();
        $job = $this->job($device);
        $newOwner = User::factory()->create();
        app(RegisterDeviceAction::class)->handle($newOwner, Platform::Android, $device->push_token, '1.0.0');
        $sender = $this->fakeSender();
        $this->app->instance(PushSender::class, $sender);

        dispatch($job);

        $this->assertSame([], $sender->sent);
        $this->assertSame($newOwner->id, $device->refresh()->user_id);
        Log::shouldHaveReceived('info')->once()->with('Push dropped: the device changed owner or token after it was queued', [
            'user_id' => $job->userId,
            'device_id' => $device->id,
        ]);
    }

    public function test_a_push_queued_before_the_device_token_changed_is_dropped(): void
    {
        $device = UserDevice::factory()->create();
        $job = $this->job($device);
        $device->update(['push_token' => 'refreshed-token']);
        $sender = $this->fakeSender();
        $this->app->instance(PushSender::class, $sender);

        dispatch($job);

        $this->assertSame([], $sender->sent);
        $this->assertSame('refreshed-token', $device->refresh()->push_token);
    }

    public function test_the_queued_job_holds_only_a_hash_of_the_token(): void
    {
        $device = UserDevice::factory()->create();

        $job = $this->job($device);

        $this->assertSame(hash('sha256', $device->push_token), $job->pushTokenHash);
        $this->assertStringNotContainsString($device->push_token, serialize($job));
    }

    #[DataProvider('retryableStatuses')]
    public function test_a_retryable_error_is_thrown_so_the_queue_retries(int $status): void
    {
        $device = UserDevice::factory()->create();

        $this->expectException(RequestException::class);

        $this->job($device)->handle($this->fakeSender($this->requestException($status)));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function retryableStatuses(): array
    {
        return [
            'expired access token' => [401],
            'rate limited' => [429],
            'server error' => [503],
        ];
    }

    public function test_a_rejected_message_fails_the_job_without_retrying_and_keeps_the_device(): void
    {
        Log::spy();
        $device = UserDevice::factory()->create();
        $job = $this->job($device)->withFakeQueueInteractions();

        $job->handle($this->fakeSender($this->requestException(400)));

        $job->assertFailed();
        $this->assertModelExists($device);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_missing_credentials_send_nothing_and_log_a_warning(): void
    {
        Http::preventStrayRequests();
        Log::spy();
        config(['services.firebase.credentials' => null]);
        $device = UserDevice::factory()->create();

        dispatch($this->job($device));

        Http::assertNothingSent();
        $this->assertModelExists($device);
        Log::shouldHaveReceived('warning')->once()->with('Push not sent: FIREBASE_CREDENTIALS is empty', [
            'user_id' => $device->user_id,
            'device_id' => $device->id,
            'title' => 'Title',
        ]);
    }

    private function job(UserDevice $device): SendPushToDevice
    {
        return new SendPushToDevice($device, 'Title', 'Body', ['type' => 'test']);
    }

    public function test_a_sender_id_mismatch_fails_the_job_without_retrying_and_keeps_the_device(): void
    {
        Log::spy();
        $device = UserDevice::factory()->create();
        $job = $this->job($device)->withFakeQueueInteractions();

        $job->handle($this->fakeSender($this->senderIdMismatch()));

        $job->assertFailed();
        $this->assertModelExists($device);
        Log::shouldHaveReceived('error')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'Push not sent: FCM rejected the message' && $context['status'] === 403,
        );
    }

    private function requestException(int $status): RequestException
    {
        return new RequestException(new Response(new Psr7Response($status, [], json_encode(['error' => ['message' => 'FCM error']]))));
    }

    private function senderIdMismatch(): RequestException
    {
        return new RequestException(new Response(new Psr7Response(403, [], json_encode(['error' => [
            'code' => 403,
            'message' => 'SenderId mismatch',
            'status' => 'PERMISSION_DENIED',
            'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'SENDER_ID_MISMATCH']],
        ]]))));
    }

    /**
     * A sender that records what it was asked to send, or throws the given exception.
     */
    private function fakeSender(?Throwable $throws = null): PushSender
    {
        return new class($throws) implements PushSender
        {
            /** @var list<array{string, string, string, array<string, string>}> */
            public array $sent = [];

            public function __construct(private readonly ?Throwable $throws) {}

            public function send(UserDevice $device, string $title, string $body, array $data): void
            {
                if ($this->throws !== null) {
                    throw $this->throws;
                }

                $this->sent[] = [$device->push_token, $title, $body, $data];
            }
        };
    }
}
