<?php

namespace Tests\Unit\Services\Sentry;

use App\Services\Sentry\ScrubSensitiveEventData;
use PHPUnit\Framework\TestCase;
use Sentry\Breadcrumb;
use Sentry\Event;

class ScrubSensitiveEventDataTest extends TestCase
{
    public function test_it_removes_secrets_from_the_request_extra_data_and_breadcrumbs_and_keeps_the_rest(): void
    {
        $event = Event::createEvent();
        $event->setRequest([
            'url' => 'https://api.example.test/api/v1/track?token=abc&page=2',
            'method' => 'POST',
            'query_string' => 'otp=1234&page=2&user%5Bpassword%5D=secret&sort=name',
            'data' => [
                'code' => '123456',
                'otp' => '1234',
                'password' => 'secret',
                'password_confirmation' => 'secret',
                'current_password' => 'old',
                'new_password' => 'new',
                'token' => 'abc',
                'lat' => 30.0444,
                'reason_id' => 3,
                'nested' => ['Code' => '654321', 'note' => 'kept'],
            ],
            'headers' => [
                'Authorization' => ['Bearer 1|secret'],
                'cookie' => ['session=secret'],
                'X-Platform' => ['android'],
            ],
            'cookies' => ['session' => 'secret'],
        ]);
        $event->setExtra(['token' => 'abc', 'order_id' => 7]);
        $event->setBreadcrumb([
            new Breadcrumb(Breadcrumb::LEVEL_INFO, Breadcrumb::TYPE_HTTP, 'http', null, [
                'http.query' => 'code=123456&page=1',
                'body' => ['password' => 'secret', 'phone' => '01000000000'],
                'otp' => '1234',
                'status_code' => 200,
            ]),
        ]);

        $scrubbed = ScrubSensitiveEventData::beforeSend($event);

        $request = $scrubbed->getRequest();
        $this->assertSame('https://api.example.test/api/v1/track?page=2', $request['url']);
        $this->assertSame('POST', $request['method']);
        $this->assertSame('page=2&sort=name', $request['query_string']);
        $this->assertSame(['lat' => 30.0444, 'reason_id' => 3, 'nested' => ['note' => 'kept']], $request['data']);
        $this->assertSame(['X-Platform' => ['android']], $request['headers']);
        $this->assertArrayNotHasKey('cookies', $request);
        $this->assertSame(['order_id' => 7], $scrubbed->getExtra());
        $this->assertSame(
            ['http.query' => 'page=1', 'body' => ['phone' => '01000000000'], 'status_code' => 200],
            $scrubbed->getBreadcrumbs()[0]->getMetadata(),
        );
    }

    public function test_a_json_string_body_is_scrubbed_too(): void
    {
        $event = Event::createEvent();
        $event->setRequest(['data' => json_encode(['code' => '123456', 'note' => 'kept'])]);

        $this->assertSame(['note' => 'kept'], ScrubSensitiveEventData::beforeSend($event)->getRequest()['data']);
    }

    public function test_it_masks_the_tracking_token_in_the_request_url_and_breadcrumb_urls(): void
    {
        $token = str_repeat('aB3', 13).'x';
        $event = Event::createEvent();
        $event->setRequest(['url' => "https://api.example.test/api/v1/track/{$token}/resend-codes?page=1&token=abc"]);
        $event->setBreadcrumb([
            new Breadcrumb(Breadcrumb::LEVEL_INFO, Breadcrumb::TYPE_HTTP, 'http', null, [
                'url' => "https://api.example.test/api/v1/track/{$token}",
                'http.url' => "/api/v1/track/{$token}",
                'route' => '/api/v1/track/{trackingToken}',
            ]),
        ]);

        $scrubbed = ScrubSensitiveEventData::beforeSend($event);

        $this->assertSame('https://api.example.test/api/v1/track/[masked]/resend-codes?page=1', $scrubbed->getRequest()['url']);
        $this->assertSame([
            'url' => 'https://api.example.test/api/v1/track/[masked]',
            'http.url' => '/api/v1/track/[masked]',
            'route' => '/api/v1/track/{trackingToken}',
        ], $scrubbed->getBreadcrumbs()[0]->getMetadata());
    }
}
