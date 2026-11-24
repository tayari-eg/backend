<?php

namespace Tests\Feature\Api\V1\Client;

use App\Domains\Accounts\Enums\AccountType;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderRating;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderRatingTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeSecond();
        $this->order = Order::factory()
            ->onTheWayWith(TayarProfile::factory()->approved()->create())
            ->create(['status' => OrderStatus::Delivered, 'delivered_at' => now()->subHour()]);
        Sanctum::actingAs(User::query()->findOrFail($this->order->sender_id));
    }

    public function test_the_business_rates_the_tayar_then_changes_the_rating_within_the_window(): void
    {
        $windowEnd = now()->subHour()->addHours(48)->toIso8601ZuluString();

        $this->putJson(route('api.v1.client.orders.rating.update', $this->order), ['stars' => 5, 'comment' => '  Careful and fast  '])
            ->assertCreated()
            ->assertJsonPath('data.stars', 5)
            ->assertJsonPath('data.comment', 'Careful and fast')
            ->assertJsonPath('data.comment_hidden', false)
            ->assertJsonPath('data.editable_until', $windowEnd);

        $this->putJson(route('api.v1.client.orders.rating.update', $this->order), ['stars' => 3])
            ->assertOk()
            ->assertJsonPath('data.stars', 3)
            ->assertJsonPath('data.comment', null);

        $rating = OrderRating::query()->sole();
        $this->assertSame([$this->order->tayar_id, 3, null], [$rating->rated_user_id, $rating->stars, $rating->comment]);
    }

    public function test_the_order_shows_can_rate_the_window_end_and_only_my_own_rating(): void
    {
        OrderRating::factory()->forOrder($this->order)->byTayar()->create(['stars' => 1, 'comment' => 'Late with the goods']);

        $this->getJson(route('api.v1.client.orders.show', $this->order))
            ->assertOk()
            ->assertJsonPath('data.can_rate', true)
            ->assertJsonPath('data.rating_ends_at', now()->subHour()->addHours(48)->toIso8601ZuluString())
            ->assertJsonPath('data.my_rating', null);

        $this->putJson(route('api.v1.client.orders.rating.update', $this->order), ['stars' => 4])->assertCreated();

        $this->getJson(route('api.v1.client.orders.index', ['status' => 'past']))
            ->assertOk()
            ->assertJsonPath('data.0.my_rating.stars', 4)
            ->assertJsonMissing(['comment' => 'Late with the goods']);

        $this->travel(48)->hours();

        $this->getJson(route('api.v1.client.orders.show', $this->order))->assertOk()->assertJsonPath('data.can_rate', false);
        $this->putJson(route('api.v1.client.orders.rating.update', $this->order), ['stars' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('errors.order.0', __('orders.rating_closed'));
    }

    public function test_the_stars_and_comment_are_validated(): void
    {
        $this->putJson(route('api.v1.client.orders.rating.update', $this->order), ['stars' => 6, 'comment' => str_repeat('a', 501)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['stars', 'comment']);
        $this->putJson(route('api.v1.client.orders.rating.update', $this->order), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('stars');
    }

    public function test_another_business_cannot_rate_the_order(): void
    {
        Sanctum::actingAs(Order::factory()->create()->sender);

        $this->putJson(route('api.v1.client.orders.rating.update', $this->order), ['stars' => 5])->assertForbidden();
        $this->assertDatabaseCount('order_ratings', 0);
    }

    public function test_an_individual_senders_delivered_order_cannot_be_rated(): void
    {
        User::query()->whereKey($this->order->sender_id)->update(['account_type' => AccountType::Individual]);

        $this->getJson(route('api.v1.client.orders.show', $this->order))->assertOk()->assertJsonPath('data.can_rate', false);
        $this->getJson(route('api.v1.client.orders.index', ['status' => 'past']))->assertOk()->assertJsonPath('data.0.can_rate', false);
        $this->putJson(route('api.v1.client.orders.rating.update', $this->order), ['stars' => 5])
            ->assertUnprocessable()
            ->assertJsonPath('errors.order.0', __('orders.rating_business_only'));
    }

    public function test_an_order_that_is_not_delivered_is_refused(): void
    {
        $this->order->forceFill(['status' => OrderStatus::NotDelivered, 'delivered_at' => null, 'not_delivered_at' => now()])->save();

        $this->putJson(route('api.v1.client.orders.rating.update', $this->order), ['stars' => 5])
            ->assertUnprocessable()
            ->assertJsonPath('errors.order.0', __('orders.rating_not_delivered'));
    }
}
