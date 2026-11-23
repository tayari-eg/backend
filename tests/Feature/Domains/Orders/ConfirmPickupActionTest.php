<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Orders\Actions\ConfirmPickupAction;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Tayars\Models\TayarProfile;
use App\Jobs\SendReceiverCodes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConfirmPickupActionTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
    }

    private function pickUp(Order $order, ?TayarProfile $tayar = null): Order
    {
        return app(ConfirmPickupAction::class)->handle(
            $order,
            User::query()->findOrFail(($tayar ?? $this->tayar)->user_id),
            [UploadedFile::fake()->image('front.jpg'), UploadedFile::fake()->image('side.jpg')],
            30.0451,
            31.2361,
        );
    }

    public function test_the_tayar_picks_up_with_photos_where_they_are_and_the_receiver_codes_are_queued_with_only_the_order_id(): void
    {
        Queue::fake();
        $order = Order::factory()->acceptedBy($this->tayar)->create();

        $pickedUp = $this->pickUp($order);

        $this->assertSame(OrderStatus::PickedUp, $pickedUp->status);
        $this->assertNotNull($pickedUp->picked_up_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Pending->value,
            'to_status' => OrderStatus::PickedUp->value,
            'changed_by' => $this->tayar->user_id,
            'lat' => '30.0451000',
            'lng' => '31.2361000',
        ]);
        $photos = $pickedUp->getMedia(Order::PICKUP_PHOTOS);
        $this->assertCount(2, $photos);
        $this->assertSame(['local'], $photos->pluck('disk')->unique()->values()->all());
        Queue::assertPushed(SendReceiverCodes::class, fn (SendReceiverCodes $job): bool => $job->orderId === $order->id);
        // The codes are only made when the job runs, so the queued job can't carry them.
        $this->assertNull($order->refresh()->delivery_code_hash);
    }

    public function test_only_the_tayar_who_accepted_picks_up_and_only_once(): void
    {
        $order = Order::factory()->acceptedBy($this->tayar)->create();
        $other = $this->onlineTayar(30.0460, 31.2370);

        foreach ([fn () => $this->pickUp($order, $other), fn () => $this->pickUp(Order::factory()->create())] as $attempt) {
            try {
                $attempt();
                $this->fail('Expected the pickup to be refused.');
            } catch (ValidationException $exception) {
                $this->assertSame([__('orders.wrong_step')], $exception->errors()['status']);
            }
        }

        $this->pickUp($order);
        $this->expectException(ValidationException::class);
        $this->pickUp($order);
    }
}
