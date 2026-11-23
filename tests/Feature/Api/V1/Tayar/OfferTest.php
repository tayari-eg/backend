<?php

namespace Tests\Feature\Api\V1\Tayar;

use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfferTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'en');
        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
        Sanctum::actingAs(User::query()->findOrFail($this->tayar->user_id));
    }

    /**
     * @return list<int>
     */
    private function listedIds(): array
    {
        return collect($this->getJson(route('api.v1.tayar.offers.index'))->assertOk()->json('data'))->pluck('id')->all();
    }

    public function test_my_live_offers_list_the_trip_and_my_earning_without_phones(): void
    {
        $this->freezeSecond();
        $order = Order::factory()->create(['code' => 'TY-ABC234']);
        $offer = OrderOffer::factory()->forTayar($this->tayar)->create(['order_id' => $order->id, 'tayar_earning' => '36.00']);

        $response = $this->getJson(route('api.v1.tayar.offers.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $offer->id)
            ->assertJsonPath('data.0.status', 'offered')
            ->assertJsonPath('data.0.tayar_earning', '36.00')
            ->assertJsonPath('data.0.expires_in_seconds', 60)
            ->assertJsonPath('data.0.order.code', 'TY-ABC234')
            ->assertJsonPath('data.0.order.mode.code', 'zone')
            ->assertJsonPath('data.0.order.pickup.lat', '30.0444000')
            ->assertJsonPath('data.0.order.distance_km', '4.00')
            ->assertJsonStructure(['meta' => ['next_cursor']]);

        $this->assertArrayNotHasKey('receiver', $response->json('data.0.order'));
        $this->assertArrayNotHasKey('contact_phone', $response->json('data.0.order.pickup'));
    }

    public function test_an_offer_past_its_time_disappears_before_the_sweep_writes_it(): void
    {
        $offer = OrderOffer::factory()->forTayar($this->tayar)->create();

        $this->travel(61)->seconds();

        $this->assertSame([], $this->listedIds());
        $this->assertSame(OfferStatus::Offered, $offer->refresh()->status);
    }

    public function test_offers_of_others_of_an_old_session_or_of_a_taken_order_are_hidden_and_offline_lists_none(): void
    {
        $mine = OrderOffer::factory()->forTayar($this->tayar)->create();
        OrderOffer::factory()->create();
        OrderOffer::factory()->forTayar($this->tayar)->create(['order_id' => Order::factory()->withStatus(OrderStatus::Pending)]);
        OrderOffer::factory()->forTayar($this->tayar)->withStatus(OfferStatus::Withdrawn)->create();

        $this->assertSame([$mine->id], $this->listedIds());

        // A new session (another radius) leaves the old session's offer out.
        app(GoOnlineAction::class)->handle($this->tayar, 30.0450, 31.2360, $this->zoneWorkSessionSettings('7'));
        $this->assertSame([], $this->listedIds());

        $this->postJson(route('api.v1.tayar.offline'))->assertOk();
        $this->assertSame([], $this->listedIds());
    }

    public function test_accept_returns_the_order_with_the_people_to_call(): void
    {
        $order = Order::factory()->create(['receiver_phone' => '+201099999999']);
        $offer = OrderOffer::factory()->forTayar($this->tayar)->create(['order_id' => $order->id]);

        $this->postJson(route('api.v1.tayar.offers.accept', $offer))
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.receiver.phone', '+201099999999')
            ->assertJsonPath('data.sender.phone', User::query()->whereKey($order->sender_id)->value('phone'))
            ->assertJsonPath('data.tayar_earning', '32.00');
    }

    public function test_accepting_an_expired_offer_is_a_422_on_offer(): void
    {
        $offer = OrderOffer::factory()->forTayar($this->tayar)->overdue()->create();

        $this->postJson(route('api.v1.tayar.offers.accept', $offer))
            ->assertUnprocessable()
            ->assertJsonPath('errors.offer.0', 'This offer has expired.');
    }

    public function test_reject_is_204_and_the_order_is_never_offered_to_me_again(): void
    {
        $offer = OrderOffer::factory()->forTayar($this->tayar)->create();

        $this->postJson(route('api.v1.tayar.offers.reject', $offer))->assertNoContent();
        $this->postJson(route('api.v1.tayar.offers.reject', $offer))->assertNoContent();

        $this->assertSame(OfferStatus::Rejected, $offer->refresh()->status);
        $this->assertSame(1, OrderOffer::query()->where('tayar_id', $this->tayar->user_id)->count());
    }

    public function test_someone_elses_offer_is_403(): void
    {
        $offer = OrderOffer::factory()->create();

        $this->postJson(route('api.v1.tayar.offers.accept', $offer))->assertForbidden();
        $this->postJson(route('api.v1.tayar.offers.reject', $offer))->assertForbidden();
        $this->postJson(route('api.v1.tayar.offers.accept', 999999))->assertNotFound();

        $this->assertSame(OfferStatus::Offered, $offer->refresh()->status);
    }

    public function test_a_client_who_is_not_a_tayar_is_403(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.tayar.offers.index'))->assertForbidden();
    }
}
