<?php

namespace Tests\Feature\Api\V1\Public;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Tayars\Models\TayarProfile;
use App\Jobs\SendReceiverCodes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
        User::query()->whereKey($this->tayar->user_id)->update(['name' => 'Ahmed Mostafa Ali']);
    }

    public function test_on_the_way_the_receiver_sees_the_status_the_tayars_first_name_vehicle_and_position_but_no_phones_or_codes(): void
    {
        $order = Order::factory()->onTheWayWith($this->tayar)->create(['receiver_phone' => '+201099999999']);
        $codes = $this->sendReceiverCodes($order);

        $response = $this->getJson(route('api.v1.public.tracking.show', $order->tracking_token))
            ->assertOk()
            ->assertJsonPath('data.code', $order->code)
            ->assertJsonPath('data.status', 'ongoing')
            ->assertJsonPath('data.tayar.first_name', 'Ahmed')
            ->assertJsonPath('data.tayar.vehicle.plate_number', fn (?string $plate): bool => $plate !== null)
            ->assertJsonPath('data.location.lat', '30.0450000')
            ->assertJsonPath('data.can_resend_codes', true)
            ->assertJsonPath('data.is_finished', false);

        $body = $response->getContent();
        foreach (['+201099999999', User::query()->whereKey($this->tayar->user_id)->value('phone'), $codes['delivery'], $codes['refusal'], 'code_hash', 'goods_value', 'shipping_fee'] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
    }

    public function test_before_pickup_and_after_the_end_there_is_no_position(): void
    {
        $accepted = Order::factory()->acceptedBy($this->tayar)->create();
        $delivered = Order::factory()->onTheWayWith($this->tayar)->create(['status' => OrderStatus::Delivered, 'delivered_at' => now()]);
        $looking = Order::factory()->create();

        $this->getJson(route('api.v1.public.tracking.show', $accepted->tracking_token))->assertOk()->assertJsonPath('data.location', null)->assertJsonPath('data.tayar.first_name', 'Ahmed');
        $this->getJson(route('api.v1.public.tracking.show', $delivered->tracking_token))
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonPath('data.location', null)
            ->assertJsonPath('data.can_resend_codes', false)
            ->assertJsonPath('data.is_finished', true);
        $this->getJson(route('api.v1.public.tracking.show', $looking->tracking_token))->assertOk()->assertJsonPath('data.tayar', null);
    }

    public function test_an_unknown_or_malformed_token_is_404(): void
    {
        $this->getJson('/api/v1/track/'.str_repeat('a', 40))->assertNotFound();
        $this->getJson('/api/v1/track/short')->assertNotFound();
    }

    public function test_the_page_is_throttled_per_ip(): void
    {
        config(['tayari.tracking.requests_per_minute' => 2]);
        $token = Order::factory()->create()->tracking_token;

        $this->getJson(route('api.v1.public.tracking.show', $token))->assertOk();
        $this->getJson(route('api.v1.public.tracking.show', $token))->assertOk();
        $this->getJson(route('api.v1.public.tracking.show', $token))->assertTooManyRequests();
    }

    public function test_the_receiver_asks_for_new_codes_while_the_goods_are_on_the_way_a_few_times_an_hour(): void
    {
        Queue::fake();
        config(['tayari.tracking.resend_codes_per_hour' => 2]);
        $order = Order::factory()->pickedUpBy($this->tayar)->create();

        $this->postJson(route('api.v1.public.tracking.resend-codes', $order->tracking_token))->assertNoContent();
        $this->postJson(route('api.v1.public.tracking.resend-codes', $order->tracking_token))->assertNoContent();
        $this->postJson(route('api.v1.public.tracking.resend-codes', $order->tracking_token))->assertTooManyRequests();

        Queue::assertPushed(SendReceiverCodes::class, 2);
        Queue::assertPushed(SendReceiverCodes::class, fn (SendReceiverCodes $job): bool => $job->orderId === $order->id);

        $this->postJson(route('api.v1.public.tracking.resend-codes', Order::factory()->acceptedBy($this->tayar)->create()->tracking_token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }
}
