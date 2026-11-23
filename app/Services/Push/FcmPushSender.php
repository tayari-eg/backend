<?php

namespace App\Services\Push;

use App\Contracts\PushSender;
use App\Domains\Accounts\Models\UserDevice;
use App\Exceptions\InvalidPushTokenException;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sends through the FCM HTTP v1 API with a service account access token, cached until shortly before it expires.
 */
class FcmPushSender implements PushSender
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const FCM_ERROR_TYPE = 'type.googleapis.com/google.firebase.fcm.v1.FcmError';

    private const UNREGISTERED = 'UNREGISTERED';

    public const SENDER_ID_MISMATCH = 'SENDER_ID_MISMATCH';

    private const TOKEN_EXPIRY_MARGIN_SECONDS = 300;

    private const TIMEOUT_SECONDS = 10;

    private const CONNECT_TIMEOUT_SECONDS = 5;

    /**
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     */
    public function __construct(private readonly array $credentials) {}

    public function send(UserDevice $device, string $title, string $body, array $data): void
    {
        $message = [
            'token' => $device->push_token,
            'notification' => ['title' => $title, 'body' => $body],
            'android' => ['priority' => 'HIGH'],
            'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
        ];

        if ($data !== []) {
            $message['data'] = $data;
        }

        $response = Http::withToken($this->accessToken())
            ->timeout(self::TIMEOUT_SECONDS)
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->post("https://fcm.googleapis.com/v1/projects/{$this->credentials['project_id']}/messages:send", ['message' => $message]);

        if ($response->successful()) {
            return;
        }

        // An expired or revoked access token: forget it so the job's retry fetches a new one.
        if ($response->unauthorized()) {
            Cache::forget($this->accessTokenCacheKey());
        }

        $deadTokenErrorCode = $this->deadTokenErrorCode($response);

        if ($deadTokenErrorCode !== null) {
            throw new InvalidPushTokenException($deadTokenErrorCode);
        }

        $response->throw();
    }

    /**
     * The FcmError code in an FCM error response, e.g. UNREGISTERED or SENDER_ID_MISMATCH.
     */
    public static function errorCode(Response $response): ?string
    {
        return collect($response->json('error.details', []))
            ->firstWhere('@type', self::FCM_ERROR_TYPE)['errorCode'] ?? null;
    }

    /**
     * SENDER_ID_MISMATCH is not a dead token: the credentials belong to another Firebase project, so it stays a 403 error.
     * INVALID_ARGUMENT is also returned for a bad payload, so it only counts when the message names the registration token.
     */
    private function deadTokenErrorCode(Response $response): ?string
    {
        $fcmErrorCode = self::errorCode($response);

        if ($fcmErrorCode === self::UNREGISTERED) {
            return $fcmErrorCode;
        }

        $isInvalidArgument = $fcmErrorCode === 'INVALID_ARGUMENT' || $response->json('error.status') === 'INVALID_ARGUMENT';

        if ($isInvalidArgument && Str::contains((string) $response->json('error.message'), 'registration token', ignoreCase: true)) {
            return 'INVALID_ARGUMENT';
        }

        return null;
    }

    private function accessToken(): string
    {
        $cacheKey = $this->accessTokenCacheKey();
        $cachedToken = Cache::get($cacheKey);

        if (is_string($cachedToken)) {
            return $cachedToken;
        }

        // Http::buildHandlerStack() keeps the token request inside Laravel's HTTP client, so Http::fake() covers it.
        $client = new Client([
            'handler' => Http::buildHandlerStack(),
            'timeout' => self::TIMEOUT_SECONDS,
            'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
        ]);

        $token = (new ServiceAccountCredentials(self::SCOPE, $this->credentials))
            ->fetchAuthToken(HttpHandlerFactory::build($client, false));

        Cache::put(
            $cacheKey,
            $token['access_token'],
            now()->addSeconds(max(60, (int) $token['expires_in'] - self::TOKEN_EXPIRY_MARGIN_SECONDS)),
        );

        return $token['access_token'];
    }

    private function accessTokenCacheKey(): string
    {
        return 'fcm:access_token:'.$this->credentials['client_email'];
    }
}
