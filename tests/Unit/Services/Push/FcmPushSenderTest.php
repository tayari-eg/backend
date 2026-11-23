<?php

namespace Tests\Unit\Services\Push;

use App\Domains\Accounts\Models\UserDevice;
use App\Exceptions\InvalidPushTokenException;
use App\Services\Push\FcmPushSender;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FcmPushSenderTest extends TestCase
{
    private const FCM_URL = 'https://fcm.googleapis.com/v1/projects/tayari-test/messages:send';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_it_sends_the_notification_to_the_device_token_with_a_bearer_access_token(): void
    {
        $this->fakeFcm(Http::response(['name' => 'projects/tayari-test/messages/1']));

        $this->sender()->send($this->device(), 'Title', 'Body', ['type' => 'test', 'order_id' => '5']);

        Http::assertSent(fn (Request $request): bool => $request->url() === self::TOKEN_URL
            && $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer');
        Http::assertSent(fn (Request $request): bool => $request->url() === self::FCM_URL
            && $request->hasHeader('Authorization', 'Bearer ya29.test-access-token')
            && $request['message'] === [
                'token' => 'device-token',
                'notification' => ['title' => 'Title', 'body' => 'Body'],
                'android' => ['priority' => 'HIGH'],
                'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                'data' => ['type' => 'test', 'order_id' => '5'],
            ]);
    }

    public function test_the_access_token_is_reused_until_it_expires(): void
    {
        $this->fakeFcm(Http::response(['name' => 'projects/tayari-test/messages/1']));
        $sender = $this->sender();

        $sender->send($this->device(), 'Title', 'Body', ['type' => 'test']);
        $sender->send($this->device(), 'Title', 'Body', ['type' => 'test']);

        Http::assertSentCount(3);
        $this->assertCount(1, Http::recorded(fn (Request $request): bool => $request->url() === self::TOKEN_URL));
    }

    public function test_a_401_forgets_the_access_token_so_the_next_send_fetches_a_new_one(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::sequence()
                ->push(['access_token' => 'ya29.expired', 'expires_in' => 3599, 'token_type' => 'Bearer'])
                ->push(['access_token' => 'ya29.fresh', 'expires_in' => 3599, 'token_type' => 'Bearer']),
            self::FCM_URL => Http::sequence()
                ->push(['error' => ['code' => 401, 'message' => 'Request had invalid authentication credentials.', 'status' => 'UNAUTHENTICATED']], 401)
                ->push(['name' => 'projects/tayari-test/messages/1']),
        ]);
        $sender = $this->sender();

        try {
            $sender->send($this->device(), 'Title', 'Body', ['type' => 'test']);
            $this->fail('No RequestException was thrown for the 401.');
        } catch (RequestException $exception) {
            $this->assertSame(401, $exception->response->status());
        }

        $this->assertFalse(Cache::has('fcm:access_token:push@tayari-test.iam.gserviceaccount.com'));

        $sender->send($this->device(), 'Title', 'Body', ['type' => 'test']);

        $this->assertCount(2, Http::recorded(fn (Request $request): bool => $request->url() === self::TOKEN_URL));
        $lastFcmRequest = Http::recorded(fn (Request $request): bool => $request->url() === self::FCM_URL)->last()[0];
        $this->assertTrue($lastFcmRequest->hasHeader('Authorization', 'Bearer ya29.fresh'));
    }

    /**
     * @param  array<string, mixed>  $error
     */
    #[DataProvider('deadTokenErrors')]
    public function test_a_dead_token_throws_an_invalid_push_token_exception(int $status, array $error, string $expectedErrorCode): void
    {
        $this->fakeFcm(Http::response(['error' => $error], $status));

        try {
            $this->sender()->send($this->device(), 'Title', 'Body', ['type' => 'test']);
            $this->fail('No InvalidPushTokenException was thrown.');
        } catch (InvalidPushTokenException $exception) {
            $this->assertSame($expectedErrorCode, $exception->errorCode);
        }
    }

    /**
     * @return array<string, array{int, array<string, mixed>, string}>
     */
    public static function deadTokenErrors(): array
    {
        return [
            'unregistered' => [404, self::fcmError(404, 'Requested entity was not found.', 'NOT_FOUND', 'UNREGISTERED'), 'UNREGISTERED'],
            'invalid registration token' => [400, self::fcmError(400, 'The registration token is not a valid FCM registration token', 'INVALID_ARGUMENT', 'INVALID_ARGUMENT'), 'INVALID_ARGUMENT'],
        ];
    }

    public function test_a_sender_id_mismatch_is_a_403_error_not_a_dead_token(): void
    {
        $this->fakeFcm(Http::response(['error' => self::fcmError(403, 'SenderId mismatch', 'PERMISSION_DENIED', 'SENDER_ID_MISMATCH')], 403));

        try {
            $this->sender()->send($this->device(), 'Title', 'Body', ['type' => 'test']);
            $this->fail('No RequestException was thrown for the sender id mismatch.');
        } catch (RequestException $exception) {
            $this->assertSame(403, $exception->response->status());
            $this->assertSame('SENDER_ID_MISMATCH', FcmPushSender::errorCode($exception->response));
        }
    }

    public function test_an_invalid_argument_that_does_not_name_the_registration_token_is_not_a_dead_token(): void
    {
        $this->fakeFcm(Http::response(['error' => self::fcmError(400, "Invalid value at 'message.data[0].value'", 'INVALID_ARGUMENT', 'INVALID_ARGUMENT')], 400));

        $this->expectException(RequestException::class);

        $this->sender()->send($this->device(), 'Title', 'Body', ['type' => 'test']);
    }

    public function test_a_server_error_throws_a_request_exception(): void
    {
        $this->fakeFcm(Http::response(['error' => ['code' => 503, 'message' => 'The service is currently unavailable.', 'status' => 'UNAVAILABLE']], 503));

        $this->expectException(RequestException::class);

        $this->sender()->send($this->device(), 'Title', 'Body', ['type' => 'test']);
    }

    /**
     * @return array<string, mixed>
     */
    private static function fcmError(int $code, string $message, string $status, string $errorCode): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'status' => $status,
            'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => $errorCode]],
        ];
    }

    private function fakeFcm(mixed $fcmResponse): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'ya29.test-access-token', 'expires_in' => 3599, 'token_type' => 'Bearer']),
            self::FCM_URL => $fcmResponse,
        ]);
    }

    /**
     * A throwaway key generated for each test, so no real or committed key is ever used.
     */
    private function sender(): FcmPushSender
    {
        openssl_pkey_export(openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]), $privateKey);

        return new FcmPushSender([
            'type' => 'service_account',
            'project_id' => 'tayari-test',
            'client_email' => 'push@tayari-test.iam.gserviceaccount.com',
            'private_key' => $privateKey,
        ]);
    }

    private function device(): UserDevice
    {
        return UserDevice::factory()->make(['user_id' => 1, 'push_token' => 'device-token']);
    }
}
