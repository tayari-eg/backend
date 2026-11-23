<?php

namespace Tests\Feature\Api\V1\Client;

use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\UserDevice;
use App\Domains\Catalog\Models\Category;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Models\TayarWorkSession;
use App\Domains\Tayars\Models\Vehicle;
use App\Jobs\SendPushToDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'en');

        config(['tayari.orders.road_distance_multiplier' => '1.00']);
        PricingRule::factory()->create(['mode_id' => $this->zoneMode()->id]);
        $this->category = Category::factory()->childOf(Category::factory()->create())->create();
        $this->sender = BusinessProfile::factory()->approved()->create()->user;
        Sanctum::actingAs($this->sender);
    }

    /**
     * A 4 km zone trip priced at 40.00.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(),
            'category_id' => $this->category->id,
            'details' => 'Two shirts in a bag.',
            'pickup_address' => '10 Example St',
            'pickup_lat' => 30.0,
            'pickup_lng' => 31.2,
            'pickup_contact_phone' => '0111 222 3333',
            'dropoff_address' => '20 Example St',
            'dropoff_lat' => 30.0359729,
            'dropoff_lng' => 31.2,
            'receiver_name' => 'Mona',
            'receiver_phone' => '01012345678',
            'goods_value' => 250,
            'shipping_paid_by' => 'receiver',
            'shipping_fee' => '40.00',
            ...$overrides,
        ];
    }

    public function test_an_approved_business_creates_an_order_at_the_estimated_price(): void
    {
        $response = $this->postJson(route('api.v1.client.orders.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'looking_for_tayar')
            ->assertJsonPath('data.can_cancel', true)
            ->assertJsonPath('data.category.id', $this->category->id)
            ->assertJsonPath('data.pickup.lat', '30.0000000')
            ->assertJsonPath('data.pickup.contact_phone', '+201112223333')
            ->assertJsonPath('data.receiver.phone', '+201012345678')
            ->assertJsonPath('data.goods_value', '250.00')
            ->assertJsonPath('data.shipping_paid_by', 'receiver')
            ->assertJsonPath('data.amount_to_collect', '290.00')
            ->assertJsonPath('data.distance_km', '4.00')
            ->assertJsonPath('data.shipping_fee', '40.00')
            ->assertJsonPath('data.cancel_reason', null)
            ->assertJsonMissingPath('data.tayar_earning')
            ->assertJsonMissingPath('data.platform_fee')
            ->assertJsonMissingPath('data.mode');

        $this->assertMatchesRegularExpression('/^TY-[A-Z2-9]{6}$/', $response->json('data.code'));
        $this->assertDatabaseHas('order_status_histories', ['to_status' => 'looking_for_tayar', 'changed_by' => $this->sender->id]);
    }

    public function test_a_double_tap_returns_the_same_order_with_200(): void
    {
        $payload = $this->payload();

        $first = $this->postJson(route('api.v1.client.orders.store'), $payload)->assertCreated();
        $this->postJson(route('api.v1.client.orders.store'), $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_a_changed_price_is_refused_and_nothing_is_created(): void
    {
        $this->postJson(route('api.v1.client.orders.store'), $this->payload(['shipping_fee' => 35]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.shipping_fee.0', __('orders.price_changed', ['fee' => '40.00'], 'en'));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_points_less_than_100_m_apart_are_refused_and_nothing_is_created(): void
    {
        $this->postJson(route('api.v1.client.orders.store'), $this->payload(['dropoff_lat' => 30.0004497]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.route.0', __('orders.route_too_short', ['min' => '100'], 'en'));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_the_fields_are_validated(): void
    {
        $this->postJson(route('api.v1.client.orders.store'), [
            'idempotency_key' => 'not-a-uuid',
            'receiver_phone' => '12345',
            'pickup_contact_phone' => 'abc',
            'goods_value' => 1.234,
            'shipping_paid_by' => 'tayar',
            'shipping_fee' => -1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'idempotency_key', 'category_id', 'pickup_address', 'pickup_lat', 'pickup_lng', 'pickup_contact_phone',
                'dropoff_address', 'dropoff_lat', 'dropoff_lng', 'receiver_name', 'receiver_phone', 'goods_value',
                'shipping_paid_by', 'shipping_fee',
            ]);

        $this->postJson(route('api.v1.client.orders.store'), $this->payload(['goods_value' => 1000000.01]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['goods_value']);
    }

    public function test_a_business_that_is_not_approved_cannot_create(): void
    {
        Sanctum::actingAs(BusinessProfile::factory()->create()->user);

        $this->postJson(route('api.v1.client.orders.store'), $this->payload())
            ->assertForbidden()
            ->assertJsonPath('message', __('orders.business_not_approved', locale: 'en'));
    }

    public function test_my_orders_lists_active_by_default_and_past_on_request_newest_first_with_cursor_pages(): void
    {
        $older = Order::factory()->forSender($this->sender)->create();
        $newer = Order::factory()->forSender($this->sender)->create();
        $delivered = Order::factory()->forSender($this->sender)->withStatus(OrderStatus::Delivered)->create();
        $cancelled = Order::factory()->forSender($this->sender)->cancelled()->create();
        Order::factory()->create();

        $this->getJson(route('api.v1.client.orders.index', ['per_page' => 1]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonStructure(['meta' => ['next_cursor', 'prev_cursor', 'per_page'], 'links' => ['next']]);

        $this->assertSame([$newer->id, $older->id], collect($this->getJson(route('api.v1.client.orders.index'))->json('data'))->pluck('id')->all());

        $nextCursor = $this->getJson(route('api.v1.client.orders.index', ['per_page' => 1]))->json('meta.next_cursor');
        $this->getJson(route('api.v1.client.orders.index', ['per_page' => 1, 'cursor' => $nextCursor]))
            ->assertJsonPath('data.0.id', $older->id);

        $this->getJson(route('api.v1.client.orders.index', ['status' => 'past']))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $cancelled->id)
            ->assertJsonPath('data.0.can_cancel', false)
            ->assertJsonPath('data.0.cancel_reason.id', $cancelled->cancel_reason_id)
            ->assertJsonPath('data.1.id', $delivered->id);

        $this->getJson(route('api.v1.client.orders.index', ['status' => 'all']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_i_see_my_order_but_not_someone_elses(): void
    {
        $mine = Order::factory()->forSender($this->sender)->create();
        $theirs = Order::factory()->create();

        $this->getJson(route('api.v1.client.orders.show', $mine))->assertOk()->assertJsonPath('data.code', $mine->code);
        $this->getJson(route('api.v1.client.orders.show', $theirs))->assertForbidden();
        $this->getJson(route('api.v1.client.orders.show', 999999))->assertNotFound();
    }

    public function test_i_cancel_my_order_with_a_reason_and_a_note(): void
    {
        $order = Order::factory()->forSender($this->sender)->create();
        $reason = OrderReason::factory()->create();

        $this->postJson(route('api.v1.client.orders.cancel', $order), ['cancel_reason_id' => $reason->id, 'note' => 'Not needed today.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.can_cancel', false)
            ->assertJsonPath('data.cancel_reason.id', $reason->id);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'looking_for_tayar',
            'to_status' => 'cancelled',
            'changed_by' => $this->sender->id,
            'note' => 'Not needed today.',
        ]);
    }

    public function test_the_cancel_reason_must_be_an_active_cancel_reason(): void
    {
        $order = Order::factory()->forSender($this->sender)->create();

        foreach ([null, OrderReason::factory()->inactive()->create()->id, OrderReason::factory()->notDelivered()->create()->id] as $reasonId) {
            $this->postJson(route('api.v1.client.orders.cancel', $order), ['cancel_reason_id' => $reasonId])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['cancel_reason_id']);
        }
    }

    public function test_i_cancel_an_accepted_order_for_free_within_the_free_period_and_the_tayar_is_pushed(): void
    {
        Queue::fake();
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        UserDevice::factory()->for(User::query()->findOrFail($tayar->user_id))->create();
        $order = Order::factory()->forSender($this->sender)->acceptedBy($tayar)->create();

        $this->postJson(route('api.v1.client.orders.cancel', $order), ['cancel_reason_id' => OrderReason::factory()->create()->id])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancelled_by_type', 'sender')
            ->assertJsonPath('data.cancel_fee', '0.00');

        Queue::assertPushed(SendPushToDevice::class, fn (SendPushToDevice $job): bool => $job->userId === $tayar->user_id
            && $job->data === ['type' => 'order_cancelled', 'order_id' => (string) $order->id, 'cancelled_by_type' => 'sender']);
    }

    public function test_after_the_free_period_my_cancel_records_the_fee_owed_to_the_tayar_capped_at_the_shipping_fee(): void
    {
        Queue::fake();
        config(['tayari.matching.cancel_fee' => '50.00']);
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        UserDevice::factory()->for(User::query()->findOrFail($tayar->user_id))->create();
        $order = Order::factory()->forSender($this->sender)->acceptedBy($tayar)->create();

        $this->travel(config()->integer('tayari.matching.free_cancel_seconds') + 1)->seconds();

        $this->getJson(route('api.v1.client.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.can_cancel', true)
            ->assertJsonPath('data.cancel_fee_now', '40.00');

        $this->postJson(route('api.v1.client.orders.cancel', $order), ['cancel_reason_id' => OrderReason::factory()->create()->id])
            ->assertOk()
            ->assertJsonPath('data.cancel_fee', '40.00')
            ->assertJsonPath('data.cancel_fee_now', '0.00')
            ->assertJsonPath('data.free_cancel_until', null);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'cancel_fee' => '40.00', 'cancel_fee_owed_to_tayar_id' => $tayar->user_id]);
        Queue::assertPushed(SendPushToDevice::class, fn (SendPushToDevice $job): bool => $job->userId === $tayar->user_id
            && $job->data['type'] === 'order_cancelled');
    }

    public function test_i_cannot_cancel_after_pickup(): void
    {
        $order = Order::factory()->forSender($this->sender)->withStatus(OrderStatus::PickedUp)->create();

        $this->postJson(route('api.v1.client.orders.cancel', $order), ['cancel_reason_id' => OrderReason::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', __('orders.cannot_cancel', locale: 'en'));

        $this->assertSame(OrderStatus::PickedUp, $order->refresh()->status);
    }

    public function test_a_new_order_is_offered_to_an_online_tayar_and_shows_until_when_the_search_runs(): void
    {
        $this->freezeSecond();
        $tayar = $this->onlineTayar(30.0010, 31.2000);

        $response = $this->postJson(route('api.v1.client.orders.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'looking_for_tayar')
            ->assertJsonPath('data.tayar', null)
            ->assertJsonPath('data.accepted_at', null)
            ->assertJsonPath('data.cancelled_by_type', null)
            ->assertJsonPath('data.search_ends_at', now()->addMinutes(config()->integer('tayari.matching.search_timeout_minutes'))->toIso8601ZuluString());

        $this->assertSame([$tayar->user_id], OrderOffer::query()->where('order_id', $response->json('data.id'))->pluck('tayar_id')->all());
    }

    public function test_after_a_tayar_accepts_i_see_the_tayar_the_vehicle_and_what_cancelling_costs(): void
    {
        $this->freezeSecond();
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $vehicle = Vehicle::query()->findOrFail(TayarWorkSession::query()->whereKey($tayar->current_work_session_id)->value('vehicle_id'));
        $tayarUser = User::query()->findOrFail($tayar->user_id);
        $order = Order::factory()->forSender($this->sender)->acceptedBy($tayar)->create();

        $this->getJson(route('api.v1.client.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.can_cancel', true)
            ->assertJsonPath('data.free_cancel_until', now()->addSeconds(config()->integer('tayari.matching.free_cancel_seconds'))->toIso8601ZuluString())
            ->assertJsonPath('data.cancel_fee_now', '0.00')
            ->assertJsonPath('data.cancel_fee', '0.00')
            ->assertJsonPath('data.search_ends_at', null)
            ->assertJsonPath('data.tayar.name', $tayarUser->name)
            ->assertJsonPath('data.tayar.phone', $tayarUser->phone)
            ->assertJsonPath('data.tayar.vehicle.plate_number', $vehicle->plate_number)
            ->assertJsonPath('data.tayar.vehicle.model', $vehicle->model)
            ->assertJsonPath('data.tayar.vehicle.color', $vehicle->color)
            ->assertJsonPath('data.tayar.vehicle.type', fn (?string $type): bool => $type !== null)
            ->assertJsonPath('data.tayar.location.lat', '30.0450000')
            ->assertJsonPath('data.tayar.location.lng', '31.2360000');
    }

    public function test_the_tayar_phone_is_gone_once_the_order_is_past_but_the_name_stays(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $tayarUser = User::query()->findOrFail($tayar->user_id);
        $order = Order::factory()->forSender($this->sender)->acceptedBy($tayar)->create(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);

        $this->getJson(route('api.v1.client.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.tayar.name', $tayarUser->name)
            ->assertJsonPath('data.tayar.phone', null);
    }

    public function test_an_order_cancelled_by_the_system_says_so(): void
    {
        $order = Order::factory()->forSender($this->sender)->create(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);

        $this->getJson(route('api.v1.client.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.cancelled_by_type', 'system')
            ->assertJsonPath('data.cancel_reason', null);
    }

    public function test_i_cannot_cancel_someone_elses_order(): void
    {
        $order = Order::factory()->create();

        $this->postJson(route('api.v1.client.orders.cancel', $order), ['cancel_reason_id' => OrderReason::factory()->create()->id])
            ->assertForbidden();

        $this->assertSame(OrderStatus::LookingForTayar, $order->refresh()->status);
    }

    public function test_staff_cannot_use_client_order_routes(): void
    {
        Sanctum::actingAs($this->createStaffUser());

        $this->getJson(route('api.v1.client.orders.index'))->assertForbidden();
    }

    public function test_my_orders_list_runs_the_same_queries_however_many_orders_and_photos_it_has(): void
    {
        Storage::fake('local');
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $this->createPickedUpOrdersWithPhotos(Order::factory()->forSender($this->sender)->pickedUpBy($tayar), 2);
        $this->getJson(route('api.v1.client.orders.index'))->assertOk();

        $fewer = $this->countQueries(fn () => $this->getJson(route('api.v1.client.orders.index'))->assertOk()->assertJsonCount(2, 'data'));
        $this->createPickedUpOrdersWithPhotos(Order::factory()->forSender($this->sender)->pickedUpBy($tayar), 3);
        $more = $this->countQueries(fn () => $this->getJson(route('api.v1.client.orders.index'))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.pickup_photos.1.url', fn (?string $url): bool => $url !== null));

        $this->assertSame($fewer, $more);
    }
}
