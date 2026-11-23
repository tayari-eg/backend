<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Catalog\Models\Category;
use App\Domains\Orders\Actions\CreateOrderAction;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Pricing\Actions\CalculateShippingFeeAction;
use App\Domains\Pricing\Models\PricingRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tayari.orders.road_distance_multiplier' => '1.00']);
        PricingRule::factory()->create(['mode_id' => $this->zoneMode()->id]);
        $this->sender = BusinessProfile::factory()->approved()->create()->user;
        $this->category = Category::factory()->childOf(Category::factory()->create())->create();
    }

    /**
     * A 4 km zone trip: 20 + 5 × 4 = 40.00.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function attributes(array $overrides = []): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(),
            'category_id' => $this->category->id,
            'details' => 'A small box.',
            'pickup_address' => 'Pickup street',
            'pickup_lat' => '30.0000000',
            'pickup_lng' => '31.2000000',
            'pickup_contact_phone' => null,
            'dropoff_address' => 'Drop-off street',
            'dropoff_lat' => '30.0359729',
            'dropoff_lng' => '31.2000000',
            'receiver_name' => 'Receiver',
            'receiver_phone' => '+201012345678',
            'goods_value' => '150.5',
            'shipping_paid_by' => 'receiver',
            'shipping_fee' => '40',
            ...$overrides,
        ];
    }

    public function test_the_order_is_created_looking_for_a_tayar_with_its_fee_snapshot_and_a_history_row(): void
    {
        $order = app(CreateOrderAction::class)->handle($this->sender, $this->attributes());

        $this->assertTrue($order->wasRecentlyCreated);
        $this->assertMatchesRegularExpression('/^TY-[ABCDEFGHJKLMNPQRSTUVWXYZ2-9]{6}$/', $order->code);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'sender_id' => $this->sender->id,
            'mode_id' => $this->zoneMode()->id,
            'status' => OrderStatus::LookingForTayar->value,
            'goods_value' => '150.50',
            'amount_to_collect' => '190.50',
            'distance_km' => '4.00',
            'shipping_fee' => '40.00',
            'tayar_earning' => '32.00',
            'platform_fee' => '8.00',
            'receiver_user_id' => null,
        ]);
        $this->assertNotNull($order->price_accepted_at);
        $this->assertDatabaseCount('order_status_histories', 1);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => OrderStatus::LookingForTayar->value,
            'changed_by' => $this->sender->id,
        ]);
    }

    public function test_the_sender_paying_shipping_collects_only_the_goods_value(): void
    {
        $order = app(CreateOrderAction::class)->handle($this->sender, $this->attributes(['shipping_paid_by' => 'sender']));

        $this->assertSame('150.50', $order->amount_to_collect);
    }

    public function test_the_receiver_is_linked_when_the_phone_has_an_account(): void
    {
        $receiver = User::factory()->create(['phone' => '+201012345678']);

        $order = app(CreateOrderAction::class)->handle($this->sender, $this->attributes());

        $this->assertSame($receiver->id, $order->receiver_user_id);
    }

    public function test_the_same_key_returns_the_first_order_instead_of_a_second_one(): void
    {
        $attributes = $this->attributes();

        $first = app(CreateOrderAction::class)->handle($this->sender, $attributes);
        $second = app(CreateOrderAction::class)->handle($this->sender, [...$attributes, 'receiver_name' => 'Someone else']);

        $this->assertFalse($second->wasRecentlyCreated);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_status_histories', 1);
    }

    public function test_a_racing_duplicate_hits_the_unique_index_and_returns_the_order_that_won(): void
    {
        $attributes = $this->attributes();
        $winner = Order::factory()->forSender($this->sender)->create(['idempotency_key' => $attributes['idempotency_key']]);

        // The first lookup misses, as if the other tap had not committed yet, so the insert hits the unique index.
        $action = new class(app(CalculateShippingFeeAction::class)) extends CreateOrderAction
        {
            private bool $hasLookedUp = false;

            protected function existingOrder(User $sender, string $idempotencyKey): ?Order
            {
                if (! $this->hasLookedUp) {
                    $this->hasLookedUp = true;

                    return null;
                }

                return parent::existingOrder($sender, $idempotencyKey);
            }
        };

        $order = $action->handle($this->sender, $attributes);

        $this->assertSame($winner->id, $order->id);
        $this->assertFalse($order->wasRecentlyCreated);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_the_key_of_a_deleted_order_is_refused_instead_of_returning_it(): void
    {
        $attributes = $this->attributes();
        $deleted = Order::factory()->forSender($this->sender)->create(['idempotency_key' => $attributes['idempotency_key']]);
        $deleted->delete();

        try {
            app(CreateOrderAction::class)->handle($this->sender, $attributes);
            $this->fail('Expected an idempotency key error.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('orders.idempotency_key_deleted')], $exception->errors()['idempotency_key']);
        }

        $this->assertDatabaseCount('orders', 1);
        $this->assertSoftDeleted($deleted);
    }

    public function test_the_same_key_from_another_sender_is_a_new_order(): void
    {
        $attributes = $this->attributes();
        $otherSender = BusinessProfile::factory()->approved()->create()->user;

        app(CreateOrderAction::class)->handle($this->sender, $attributes);
        app(CreateOrderAction::class)->handle($otherSender, $attributes);

        $this->assertDatabaseCount('orders', 2);
    }

    public function test_a_changed_price_is_refused_with_the_current_fee(): void
    {
        try {
            app(CreateOrderAction::class)->handle($this->sender, $this->attributes(['shipping_fee' => '35.00']));
            $this->fail('Expected a price change error.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('orders.price_changed', ['fee' => '40.00'])], $exception->errors()['shipping_fee']);
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_status_histories', 0);
    }
}
