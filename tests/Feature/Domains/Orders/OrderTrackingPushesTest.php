<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\UserDevice;
use App\Domains\Orders\Actions\CloseUndeliveredOrderAction;
use App\Domains\Orders\Actions\ConfirmPickupAction;
use App\Domains\Orders\Actions\ConfirmReceiverAction;
use App\Domains\Orders\Actions\DeliverOrderAction;
use App\Domains\Orders\Actions\MarkOrderNotDeliveredAction;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Tayars\Models\TayarProfile;
use App\Jobs\SendPushToDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderTrackingPushesTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    private User $tayarUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
        $this->tayarUser = User::query()->findOrFail($this->tayar->user_id);
        UserDevice::factory()->for($this->tayarUser)->create();
    }

    /**
     * @return list<array{int, array<string, string>}>
     */
    private function pushed(): array
    {
        return Queue::pushed(SendPushToDevice::class)->map(fn (SendPushToDevice $job): array => [$job->userId, $job->data])->values()->all();
    }

    public function test_the_sender_hears_of_every_step_and_the_tayar_of_none_they_did_themselves(): void
    {
        $order = Order::factory()->acceptedBy($this->tayar)->create();
        UserDevice::factory()->for(User::query()->findOrFail($order->sender_id))->create();
        $data = fn (string $type): array => ['type' => $type, 'order_id' => (string) $order->id];

        app(ConfirmPickupAction::class)->handle($order, $this->tayarUser, [UploadedFile::fake()->image('p.jpg')], 30.0, 31.0);
        $codes = $this->sendReceiverCodes($order);
        Queue::fake();
        app(ConfirmReceiverAction::class)->handle($order, $this->tayarUser, 30.0, 31.0);
        app(DeliverOrderAction::class)->handle($order, $this->tayarUser, $codes['delivery'], 30.0, 31.0);

        $this->assertSame([[$order->sender_id, $data('order_on_the_way')], [$order->sender_id, $data('order_delivered')]], $this->pushed());
    }

    public function test_picked_up_and_a_refusal_at_the_door_reach_only_the_sender(): void
    {
        $order = Order::factory()->acceptedBy($this->tayar)->create();
        UserDevice::factory()->for(User::query()->findOrFail($order->sender_id))->create();
        Queue::fake([SendPushToDevice::class]);

        app(ConfirmPickupAction::class)->handle($order, $this->tayarUser, [UploadedFile::fake()->image('p.jpg')], 30.0, 31.0);
        app(ConfirmReceiverAction::class)->handle($order, $this->tayarUser, 30.0, 31.0);
        $codes = $this->sendReceiverCodes($order);
        app(MarkOrderNotDeliveredAction::class)->handle($order, $this->tayarUser, $codes['refusal'], OrderReason::factory()->notDelivered()->create(), null, 30.0, 31.0);

        $this->assertSame([
            [$order->sender_id, ['type' => 'order_picked_up', 'order_id' => (string) $order->id]],
            [$order->sender_id, ['type' => 'order_on_the_way', 'order_id' => (string) $order->id]],
            [$order->sender_id, ['type' => 'order_not_delivered', 'order_id' => (string) $order->id]],
        ], $this->pushed());
    }

    public function test_when_staff_close_the_order_both_the_sender_and_the_tayar_hear(): void
    {
        $order = Order::factory()->pickedUpBy($this->tayar)->create();
        UserDevice::factory()->for(User::query()->findOrFail($order->sender_id))->create();
        Queue::fake();

        app(CloseUndeliveredOrderAction::class)->handle($order, $this->createStaffUser(StaffRole::Ops), OrderReason::factory()->notDelivered()->create(), 'Unreachable.');

        $data = ['type' => 'order_not_delivered', 'order_id' => (string) $order->id];
        $this->assertEqualsCanonicalizing([[$order->sender_id, $data], [$this->tayar->user_id, $data]], $this->pushed());
    }
}
