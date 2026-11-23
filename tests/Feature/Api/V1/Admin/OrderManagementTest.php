<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Orders\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_staff_cannot_cancel_after_a_tayar_accepted(): void
    {
        $order = Order::factory()->withStatus(OrderStatus::Pending)->create();

        $this->postJson(route('api.v1.admin.orders.cancel', $order), ['cancel_reason_id' => OrderReason::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', __('orders.cannot_cancel', locale: 'en'));
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
