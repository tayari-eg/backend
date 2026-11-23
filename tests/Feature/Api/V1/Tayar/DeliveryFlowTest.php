<?php

namespace Tests\Feature\Api\V1\Tayar;

use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeliveryFlowTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->withHeader('Accept-Language', 'en');
        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
        Sanctum::actingAs(User::query()->findOrFail($this->tayar->user_id));
    }

    /**
     * @return array{lat: float, lng: float}
     */
    private function position(): array
    {
        return ['lat' => 30.0451, 'lng' => 31.2361];
    }

    public function test_pickup_then_on_the_way_then_deliver_with_the_receivers_code(): void
    {
        $order = Order::factory()->acceptedBy($this->tayar)->create();

        $this->post(route('api.v1.tayar.orders.pickup', $order), [...$this->position(), 'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')]], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.status', 'picked_up')
            ->assertJsonCount(2, 'data.pickup_photos')
            ->assertJsonPath('data.pickup_photos.0.url', fn (?string $url): bool => $url !== null);

        // The codes job runs after the response; the next read shows they went out.
        $this->getJson(route('api.v1.tayar.orders.show', $order))->assertJsonPath('data.receiver_codes.sent_at', fn (?string $sentAt): bool => $sentAt !== null);

        $this->postJson(route('api.v1.tayar.orders.receiver-confirmed', $order), $this->position())
            ->assertOk()
            ->assertJsonPath('data.status', 'ongoing')
            ->assertJsonPath('data.receiver_codes.attempts_left', 5)
            ->assertJsonPath('data.receiver_codes.problem', null);

        $codes = $this->sendReceiverCodes($order);

        $this->postJson(route('api.v1.tayar.orders.deliver', $order), [...$this->position(), 'code' => '12345'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $response = $this->postJson(route('api.v1.tayar.orders.deliver', $order), [...$this->position(), 'code' => $codes['delivery']])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonPath('data.receiver.phone', null);

        $this->assertStringNotContainsString($codes['delivery'], $response->getContent());
        $this->assertStringNotContainsString('code_hash', $response->getContent());
    }

    public function test_the_refusal_code_marks_not_delivered_with_an_active_not_delivered_reason(): void
    {
        $order = Order::factory()->onTheWayWith($this->tayar)->create();
        $codes = $this->sendReceiverCodes($order);
        $reason = OrderReason::factory()->notDelivered()->create();
        $cancelReason = OrderReason::factory()->create();

        $this->postJson(route('api.v1.tayar.orders.not-delivered', $order), [...$this->position(), 'code' => $codes['refusal'], 'not_delivered_reason_id' => $cancelReason->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('not_delivered_reason_id');

        $this->postJson(route('api.v1.tayar.orders.not-delivered', $order), [...$this->position(), 'code' => $codes['refusal'], 'not_delivered_reason_id' => $reason->id, 'note' => 'Wrong size.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'not_delivered')
            ->assertJsonPath('data.not_delivered_reason.id', $reason->id)
            ->assertJsonPath('data.not_delivered_note', 'Wrong size.');
    }

    public function test_photos_are_required_images_one_to_four_and_the_position_is_required(): void
    {
        $order = Order::factory()->acceptedBy($this->tayar)->create();
        $images = fn (int $count): array => array_map(fn (int $i): UploadedFile => UploadedFile::fake()->image("{$i}.jpg"), range(1, $count));

        $this->post(route('api.v1.tayar.orders.pickup', $order), ['photos' => []], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photos', 'lat', 'lng']);
        $this->post(route('api.v1.tayar.orders.pickup', $order), [...$this->position(), 'photos' => $images(5)], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photos');
        $this->post(route('api.v1.tayar.orders.pickup', $order), [...$this->position(), 'photos' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photos.0');

        $this->assertSame('pending', $order->refresh()->status->value);
    }

    public function test_another_tayars_order_is_403_and_a_wrong_step_is_422(): void
    {
        $theirs = Order::factory()->acceptedBy($this->onlineTayar(30.0460, 31.2370))->create();
        $mine = Order::factory()->acceptedBy($this->tayar)->create();

        $this->postJson(route('api.v1.tayar.orders.receiver-confirmed', $theirs), $this->position())->assertForbidden();
        $this->postJson(route('api.v1.tayar.orders.receiver-confirmed', $mine), $this->position())
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', "This order can't move to that step now.");
    }

    public function test_code_entries_are_throttled_per_tayar(): void
    {
        config(['tayari.tracking.code_requests_per_minute' => 2]);
        $order = Order::factory()->onTheWayWith($this->tayar)->create();

        $this->postJson(route('api.v1.tayar.orders.deliver', $order), [...$this->position(), 'code' => '111111'])->assertUnprocessable();
        $this->postJson(route('api.v1.tayar.orders.deliver', $order), [...$this->position(), 'code' => '111111'])->assertUnprocessable();
        $this->postJson(route('api.v1.tayar.orders.deliver', $order), [...$this->position(), 'code' => '111111'])->assertTooManyRequests();
    }
}
