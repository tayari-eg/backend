<?php

namespace Tests\Feature\Api\V1\Tayar;

use App\Domains\Accounts\Enums\AccountType;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\RaterRole;
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

    private TayarProfile $tayar;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
        $this->order = Order::factory()->onTheWayWith($this->tayar)->create(['status' => OrderStatus::Delivered, 'delivered_at' => now()->subHour()]);
        Sanctum::actingAs(User::query()->findOrFail($this->tayar->user_id));
    }

    public function test_the_tayar_rates_the_business_and_sees_only_their_own_rating_on_the_order(): void
    {
        OrderRating::factory()->forOrder($this->order)->create(['stars' => 2, 'comment' => 'Slow']);

        $this->putJson(route('api.v1.tayar.orders.rating.update', $this->order), ['stars' => 5, 'comment' => 'Ready on time'])
            ->assertCreated()
            ->assertJsonPath('data.stars', 5);

        $rating = OrderRating::query()->where('rater_role', RaterRole::Tayar)->sole();
        $this->assertSame([$this->tayar->user_id, $this->order->sender_id], [$rating->rater_id, $rating->rated_user_id]);

        $this->getJson(route('api.v1.tayar.orders.show', $this->order))
            ->assertOk()
            ->assertJsonPath('data.can_rate', true)
            ->assertJsonPath('data.my_rating.stars', 5)
            ->assertJsonPath('data.my_rating.comment', 'Ready on time')
            ->assertJsonMissing(['comment' => 'Slow']);
    }

    public function test_an_individual_senders_delivered_order_cannot_be_rated(): void
    {
        User::query()->whereKey($this->order->sender_id)->update(['account_type' => AccountType::Individual]);

        $this->getJson(route('api.v1.tayar.orders.show', $this->order))->assertOk()->assertJsonPath('data.can_rate', false);
        $this->putJson(route('api.v1.tayar.orders.rating.update', $this->order), ['stars' => 5])
            ->assertUnprocessable()
            ->assertJsonPath('errors.order.0', __('orders.rating_business_only'));
        $this->assertDatabaseCount('order_ratings', 0);
    }

    public function test_another_tayar_cannot_rate_the_order(): void
    {
        Sanctum::actingAs(User::query()->findOrFail($this->onlineTayar(30.0460, 31.2370)->user_id));

        $this->putJson(route('api.v1.tayar.orders.rating.update', $this->order), ['stars' => 1])->assertForbidden();
        $this->assertDatabaseCount('order_ratings', 0);
    }
}
