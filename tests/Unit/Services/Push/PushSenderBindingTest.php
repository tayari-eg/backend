<?php

namespace Tests\Unit\Services\Push;

use App\Contracts\PushSender;
use App\Domains\Accounts\Models\UserDevice;
use App\Services\Push\FcmPushSender;
use App\Services\Push\LogPushSender;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PushSenderBindingTest extends TestCase
{
    private ?string $credentialsPath = null;

    protected function tearDown(): void
    {
        if ($this->credentialsPath !== null && is_file($this->credentialsPath)) {
            unlink($this->credentialsPath);
        }

        parent::tearDown();
    }

    public function test_empty_credentials_log_instead_of_sending(): void
    {
        config(['services.firebase.credentials' => null]);

        $sender = app(PushSender::class);

        $this->assertInstanceOf(LogPushSender::class, $sender);
        $this->assertSame('FIREBASE_CREDENTIALS is empty', $sender->reason);
    }

    public function test_a_missing_credentials_file_logs_instead_of_sending_without_the_path(): void
    {
        $path = '/nowhere/firebase-credentials.json';
        config(['services.firebase.credentials' => $path]);

        $sender = app(PushSender::class);

        $this->assertInstanceOf(LogPushSender::class, $sender);
        $this->assertSame('the FIREBASE_CREDENTIALS file is missing or unreadable', $sender->reason);
        $this->assertPathIsNotLogged($sender, $path);
    }

    public function test_a_file_that_is_not_a_service_account_json_logs_instead_of_sending_without_the_path(): void
    {
        $path = $this->writeCredentials('not json');
        config(['services.firebase.credentials' => $path]);

        $sender = app(PushSender::class);

        $this->assertInstanceOf(LogPushSender::class, $sender);
        $this->assertSame('the FIREBASE_CREDENTIALS file is not a service account JSON', $sender->reason);
        $this->assertPathIsNotLogged($sender, $path);
    }

    public function test_a_service_account_json_sends_through_fcm(): void
    {
        config(['services.firebase.credentials' => $this->writeCredentials(json_encode([
            'type' => 'service_account',
            'project_id' => 'tayari-test',
            'client_email' => 'push@tayari-test.iam.gserviceaccount.com',
            'private_key' => 'not-a-real-key',
        ]))]);

        $this->assertInstanceOf(FcmPushSender::class, app(PushSender::class));
    }

    private function assertPathIsNotLogged(PushSender $sender, string $path): void
    {
        Log::spy();

        $sender->send(UserDevice::factory()->make(['user_id' => 1]), 'Title', 'Body', ['type' => 'test']);

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => ! str_contains($message, $path) && ! in_array($path, $context, true),
        );
    }

    private function writeCredentials(string $contents): string
    {
        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'firebase-test-');
        file_put_contents($this->credentialsPath, $contents);

        return $this->credentialsPath;
    }
}
