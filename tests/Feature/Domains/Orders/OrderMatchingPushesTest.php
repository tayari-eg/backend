<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\UserDevice;
use App\Domains\Orders\Actions\AcceptOfferAction;
use App\Domains\Orders\Actions\CancelOrderAction;
use App\Domains\Orders\Actions\DispatchOrderOffersAction;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Orders\Models\OrderReason;
use App\Jobs\SendPushToDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderMatchingPushesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    private function deviceOf(int $userId): void
    {
        User::query()->whereKey($userId)->update(['locale' => Locale::En]);
        UserDevice::factory()->for(User::query()->findOrFail($userId))->create();
    }

    /**
     * @param  array<string, string>  $data
     */
    private function assertPushedTo(int $userId, array $data, ?string $body = null): void
    {
        Queue::assertPushed(SendPushToDevice::class, fn (SendPushToDevice $job): bool => $job->userId === $userId
            && $job->data === $data
            && ($body === null || $job->body === $body));
    }

    public function test_an_offer_is_pushed_to_the_tayar_after_the_response_not_through_the_queue(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $this->deviceOf($tayar->user_id);
        $order = Order::factory()->create();

        [$offer] = app(DispatchOrderOffersAction::class)->handle($order);

        Queue::assertNothingPushed();

        $this->app->terminate();

        $this->assertPushedTo(
            $tayar->user_id,
            ['type' => 'order_offer', 'order_id' => (string) $order->id, 'offer_id' => (string) $offer->id],
            __('push.order_offer.body', ['earning' => '32.00', 'distance' => $offer->distance_km], 'en'),
        );
    }

    public function test_the_sender_hears_when_a_tayar_accepts(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $order = Order::factory()->create();
        $this->deviceOf($order->sender_id);
        $offer = OrderOffer::factory()->forTayar($tayar)->create(['order_id' => $order->id]);
        $tayarUser = User::query()->findOrFail($tayar->user_id);

        app(AcceptOfferAction::class)->handle($offer, $tayarUser);

        $this->assertPushedTo($order->sender_id, ['type' => 'order_accepted', 'order_id' => (string) $order->id], __('push.order_accepted.body', ['tayar' => $tayarUser->name, 'code' => $order->code], 'en'));
    }

    public function test_the_sender_hears_when_no_tayar_accepted_in_time(): void
    {
        $order = Order::factory()->create(['price_accepted_at' => now()->subMinutes(config()->integer('tayari.matching.search_timeout_minutes'))]);
        $this->deviceOf($order->sender_id);

        app(DispatchOrderOffersAction::class)->handle($order);

        $this->assertPushedTo($order->sender_id, ['type' => 'order_no_tayar', 'order_id' => (string) $order->id]);
    }

    public function test_the_tayar_hears_who_cancelled_an_accepted_order_and_nobody_hears_of_a_cancel_before_accept(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $byStaff = Order::factory()->acceptedBy($tayar)->create();
        $bySender = Order::factory()->acceptedBy($tayar)->create();
        $waiting = Order::factory()->create();
        $this->deviceOf($tayar->user_id);
        $this->deviceOf($byStaff->sender_id);
        $this->deviceOf($bySender->sender_id);
        $this->deviceOf($waiting->sender_id);

        app(CancelOrderAction::class)->handle($byStaff, $this->createStaffUser(StaffRole::Ops), OrderReason::factory()->create());
        app(CancelOrderAction::class)->handle($bySender, $bySender->sender()->firstOrFail(), OrderReason::factory()->create());
        app(CancelOrderAction::class)->handle($waiting, $waiting->sender()->firstOrFail(), OrderReason::factory()->create());

        Queue::assertPushed(SendPushToDevice::class, 2);
        $this->assertPushedTo($tayar->user_id, ['type' => 'order_cancelled', 'order_id' => (string) $byStaff->id, 'cancelled_by_type' => 'staff']);
        $this->assertPushedTo($tayar->user_id, ['type' => 'order_cancelled', 'order_id' => (string) $bySender->id, 'cancelled_by_type' => 'sender']);
    }
}
