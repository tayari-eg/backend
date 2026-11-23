<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\UserDevice;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Orders\Actions\CancelOrderAction;
use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Orders\Models\OrderStatusHistory;
use App\Jobs\SendPushToDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'en');

        $this->staff = $this->createStaffUser(StaffRole::Ops);
        Sanctum::actingAs($this->staff);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<int>
     */
    private function listedIds(array $query = []): array
    {
        return collect($this->getJson(route('api.v1.admin.orders.index', $query))->assertOk()->json('data'))->pluck('id')->all();
    }

    public function test_every_order_is_listed_newest_first_with_sender_category_mode_and_fee_breakdown(): void
    {
        $first = Order::factory()->create();
        $second = Order::factory()->cancelled()->create();

        $this->getJson(route('api.v1.admin.orders.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.1.id', $first->id)
            ->assertJsonPath('data.1.sender.id', $first->sender_id)
            ->assertJsonPath('data.1.sender.business_name', BusinessProfile::query()->where('user_id', $first->sender_id)->value('business_name'))
            ->assertJsonPath('data.1.mode.code', 'zone')
            ->assertJsonPath('data.1.fee.shipping_fee', '40.00')
            ->assertJsonPath('data.1.fee.tayar_earning', '32.00')
            ->assertJsonPath('data.1.fee.platform_fee', '8.00')
            ->assertJsonStructure(['meta' => ['total', 'last_page'], 'links']);
    }

    public function test_filters_by_status_sender_category_mode_and_business_dates(): void
    {
        $sender = BusinessProfile::factory()->approved()->create()->user;
        $mine = Order::factory()->forSender($sender)->create();
        $cancelled = Order::factory()->cancelled()->create();
        $longDistance = Order::factory()->create(['mode_id' => Mode::factory()->longDistance()->create()->id]);

        $this->assertSame([$cancelled->id], $this->listedIds(['status' => 'cancelled']));
        $this->assertSame([$mine->id], $this->listedIds(['sender_id' => $sender->id]));
        $this->assertSame([$mine->id], $this->listedIds(['category_id' => $mine->category_id]));
        $this->assertSame([$longDistance->id], $this->listedIds(['mode_id' => $longDistance->mode_id]));

        // 23:30 UTC on 1 March is 01:30 on 2 March in Cairo.
        config(['app.business_timezone' => 'Africa/Cairo']);
        Order::query()->whereKey($mine->id)->update(['created_at' => '2026-03-01 23:30:00']);
        Order::query()->whereKey($cancelled->id)->update(['created_at' => '2026-03-01 21:30:00']);
        $this->assertSame([$mine->id], $this->listedIds(['created_from' => '2026-03-02', 'created_to' => '2026-03-02']));
        $this->assertSame([$cancelled->id], $this->listedIds(['created_from' => '2026-03-01', 'created_to' => '2026-03-01']));

        $this->getJson(route('api.v1.admin.orders.index', ['status' => 'lost', 'created_from' => '2026-03-02', 'created_to' => '2026-03-01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'created_to']);
    }

    public function test_search_finds_an_order_by_code_or_by_the_receiver_or_sender_phone(): void
    {
        $byCode = Order::factory()->create(['code' => 'TY-ABC234']);
        $byReceiver = Order::factory()->create(['receiver_phone' => '+201099999999']);
        $sender = BusinessProfile::factory()->approved()->create(['user_id' => User::factory()->business()->create(['phone' => '+201088888888'])])->user;
        $bySender = Order::factory()->forSender($sender)->create();

        $this->assertSame([$byCode->id], $this->listedIds(['search' => 'ty-abc234']));
        $this->assertSame([$byCode->id], $this->listedIds(['search' => 'abc234']));
        $this->assertSame([$byReceiver->id], $this->listedIds(['search' => '0109 999 9999']));
        $this->assertSame([$bySender->id], $this->listedIds(['search' => '01088888888']));
        $this->assertSame([], $this->listedIds(['search' => 'nothing']));
    }

    public function test_show_has_the_full_history_with_who_changed_it(): void
    {
        $order = Order::factory()->create();
        $order->statusHistories()->create(['from_status' => null, 'to_status' => OrderStatus::LookingForTayar, 'changed_by' => $order->sender_id]);

        $this->postJson(route('api.v1.admin.orders.cancel', $order), ['cancel_reason_id' => OrderReason::factory()->create()->id, 'note' => 'Duplicate order.'])
            ->assertOk();

        $this->getJson(route('api.v1.admin.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancelled_by.id', $this->staff->id)
            ->assertJsonPath('data.status_history.0.to_status', 'looking_for_tayar')
            ->assertJsonPath('data.status_history.0.changed_by.id', $order->sender_id)
            ->assertJsonPath('data.status_history.1.from_status', 'looking_for_tayar')
            ->assertJsonPath('data.status_history.1.to_status', 'cancelled')
            ->assertJsonPath('data.status_history.1.changed_by.id', $this->staff->id)
            ->assertJsonPath('data.status_history.1.note', 'Duplicate order.');
    }

    public function test_show_lists_the_history_in_id_order_even_when_rows_were_inserted_out_of_order(): void
    {
        $order = Order::factory()->cancelled()->create();
        OrderStatusHistory::factory()->for($order)->create(['id' => 20, 'from_status' => OrderStatus::LookingForTayar, 'to_status' => OrderStatus::Cancelled]);
        OrderStatusHistory::factory()->for($order)->create(['id' => 10]);

        $this->getJson(route('api.v1.admin.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.status_history.0.to_status', 'looking_for_tayar')
            ->assertJsonPath('data.status_history.1.to_status', 'cancelled');
    }

    public function test_staff_cancel_an_accepted_order_the_tayar_is_pushed_and_stays_on_the_order(): void
    {
        Queue::fake();
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        UserDevice::factory()->for(User::query()->findOrFail($tayar->user_id))->create();
        $order = Order::factory()->acceptedBy($tayar)->create(['accepted_at' => now()->subHour()]);

        $this->postJson(route('api.v1.admin.orders.cancel', $order), ['cancel_reason_id' => OrderReason::factory()->create()->id, 'note' => 'The tayar disappeared.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancelled_by_type', 'staff')
            ->assertJsonPath('data.cancel_fee', '0.00')
            ->assertJsonPath('data.cancel_fee_owed_to', null)
            ->assertJsonPath('data.tayar.id', $tayar->user_id)
            ->assertJsonPath('data.status_history.0.from_status', 'pending')
            ->assertJsonPath('data.status_history.0.changed_by.id', $this->staff->id);

        Queue::assertPushed(SendPushToDevice::class, fn (SendPushToDevice $job): bool => $job->userId === $tayar->user_id
            && $job->data === ['type' => 'order_cancelled', 'order_id' => (string) $order->id, 'cancelled_by_type' => 'staff']);
    }

    public function test_a_cancel_fee_owed_by_the_sender_shows_with_the_tayar_it_is_owed_to(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $order = Order::factory()->acceptedBy($tayar)->create(['accepted_at' => now()->subHour()]);
        app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), OrderReason::factory()->create());

        $this->getJson(route('api.v1.admin.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.cancelled_by_type', 'sender')
            ->assertJsonPath('data.cancel_fee', '20.00')
            ->assertJsonPath('data.cancel_fee_owed_to.id', $tayar->user_id);
    }

    public function test_staff_close_a_picked_up_or_ongoing_order_as_not_delivered_and_both_sides_hear(): void
    {
        Queue::fake();
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        UserDevice::factory()->for(User::query()->findOrFail($tayar->user_id))->create();
        $reason = OrderReason::factory()->notDelivered()->create();

        foreach ([Order::factory()->pickedUpBy($tayar)->create(), Order::factory()->onTheWayWith($tayar)->create()] as $order) {
            UserDevice::factory()->for(User::query()->findOrFail($order->sender_id))->create();

            $this->postJson(route('api.v1.admin.orders.not-delivered', $order), ['not_delivered_reason_id' => $reason->id, 'note' => 'Receiver unreachable.'])
                ->assertOk()
                ->assertJsonPath('data.status', 'not_delivered')
                ->assertJsonPath('data.not_delivered_reason.id', $reason->id)
                ->assertJsonPath('data.not_delivered_note', 'Receiver unreachable.')
                ->assertJsonPath('data.status_history.0.changed_by.id', $this->staff->id)
                ->assertJsonPath('data.status_history.0.lat', null);

            $data = ['type' => 'order_not_delivered', 'order_id' => (string) $order->id];
            foreach ([$order->sender_id, $tayar->user_id] as $userId) {
                Queue::assertPushed(SendPushToDevice::class, fn (SendPushToDevice $job): bool => $job->userId === $userId && $job->data === $data);
            }
        }
    }

    public function test_closing_as_not_delivered_needs_a_reason_a_note_the_right_status_and_the_permission(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $order = Order::factory()->pickedUpBy($tayar)->create();
        $reason = OrderReason::factory()->notDelivered()->create();

        $this->postJson(route('api.v1.admin.orders.not-delivered', $order), ['not_delivered_reason_id' => OrderReason::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['not_delivered_reason_id', 'note']);
        $this->postJson(route('api.v1.admin.orders.not-delivered', Order::factory()->acceptedBy($tayar)->create()), ['not_delivered_reason_id' => $reason->id, 'note' => 'x'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', __('orders.cannot_close_not_delivered', locale: 'en'));

        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));
        $this->postJson(route('api.v1.admin.orders.not-delivered', $order), ['not_delivered_reason_id' => $reason->id, 'note' => 'x'])->assertForbidden();
        $this->postJson(route('api.v1.admin.orders.resend-codes', $order))->assertForbidden();

        $this->assertSame(OrderStatus::PickedUp, $order->refresh()->status);
    }

    public function test_staff_resend_the_receiver_codes_and_see_the_timeline_with_positions_but_never_the_codes(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $order = Order::factory()->acceptedBy($tayar)->create();
        $order->moveTo(OrderStatus::PickedUp, ['picked_up_at' => now()], $tayar->user_id, lat: 30.0451, lng: 31.2361);
        $codes = $this->sendReceiverCodes($order);

        $this->postJson(route('api.v1.admin.orders.resend-codes', $order))->assertNoContent();

        $response = $this->getJson(route('api.v1.admin.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.status_history.0.to_status', 'picked_up')
            ->assertJsonPath('data.status_history.0.lat', '30.0451000')
            ->assertJsonPath('data.status_history.0.lng', '31.2361000')
            ->assertJsonPath('data.receiver_codes.attempts', 0)
            ->assertJsonPath('data.receiver_codes.locked', false)
            ->assertJsonPath('data.tracking_token', $order->tracking_token);

        $this->assertStringNotContainsString('code_hash', $response->getContent());
        $this->assertStringNotContainsString($codes['delivery'], $response->getContent());

        $this->postJson(route('api.v1.admin.orders.resend-codes', Order::factory()->create()))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_staff_cannot_cancel_after_pickup(): void
    {
        $order = Order::factory()->withStatus(OrderStatus::PickedUp)->create();

        $this->postJson(route('api.v1.admin.orders.cancel', $order), ['cancel_reason_id' => OrderReason::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', __('orders.cannot_cancel', locale: 'en'));
    }

    public function test_an_accepted_order_shows_its_tayar_vehicle_and_the_tayars_earning_and_filters_by_tayar(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $accepted = Order::factory()->acceptedBy($tayar)->create(['tayar_earning' => '36.00', 'platform_fee' => '4.00']);
        Order::factory()->create();

        $this->getJson(route('api.v1.admin.orders.show', $accepted))
            ->assertOk()
            ->assertJsonPath('data.can_cancel', true)
            ->assertJsonPath('data.tayar.id', $tayar->user_id)
            ->assertJsonPath('data.vehicle.plate_number', fn (?string $plate): bool => $plate !== null)
            ->assertJsonPath('data.work_session_id', $tayar->current_work_session_id)
            ->assertJsonPath('data.fee.tayar_earning', '36.00')
            ->assertJsonPath('data.accepted_at', fn (?string $acceptedAt): bool => $acceptedAt !== null);

        $this->assertSame([$accepted->id], $this->listedIds(['tayar_id' => $tayar->user_id]));
    }

    public function test_the_offers_of_an_order_show_who_got_them_and_what_they_did(): void
    {
        $order = Order::factory()->create();
        $rejected = OrderOffer::factory()->forTayar($this->onlineTayar(30.0450, 31.2360))->withStatus(OfferStatus::Rejected)->create(['order_id' => $order->id]);
        $overdue = OrderOffer::factory()->forTayar($this->onlineTayar(30.0460, 31.2370))->overdue()->create(['order_id' => $order->id, 'round' => 2]);
        OrderOffer::factory()->create();

        $this->getJson(route('api.v1.admin.orders.offers.index', $order))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $overdue->id)
            ->assertJsonPath('data.0.status', 'expired')
            ->assertJsonPath('data.0.round', 2)
            ->assertJsonPath('data.1.id', $rejected->id)
            ->assertJsonPath('data.1.status', 'rejected')
            ->assertJsonPath('data.1.tayar.id', $rejected->tayar_id)
            ->assertJsonPath('data.1.tayar_earning', '32.00')
            ->assertJsonPath('data.1.responded_at', fn (?string $respondedAt): bool => $respondedAt !== null)
            ->assertJsonStructure(['meta' => ['total'], 'links']);

        Sanctum::actingAs(BusinessProfile::factory()->approved()->create()->user);
        $this->getJson(route('api.v1.admin.orders.offers.index', $order))->assertForbidden();
    }

    public function test_support_can_view_but_not_cancel(): void
    {
        $order = Order::factory()->create();
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->getJson(route('api.v1.admin.orders.show', $order))->assertOk();
        $this->postJson(route('api.v1.admin.orders.cancel', $order), ['cancel_reason_id' => OrderReason::factory()->create()->id])->assertForbidden();
    }

    public function test_a_customer_cannot_use_admin_order_routes(): void
    {
        Sanctum::actingAs(BusinessProfile::factory()->approved()->create()->user);

        $this->getJson(route('api.v1.admin.orders.index'))->assertForbidden();
    }
}
