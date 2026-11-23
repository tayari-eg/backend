<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Orders\Actions\ConfirmPickupAction;
use App\Domains\Orders\Actions\ConfirmReceiverAction;
use App\Domains\Orders\Actions\DeliverOrderAction;
use App\Domains\Orders\Actions\MarkOrderNotDeliveredAction;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every tayar step from every status: only the design's next step works, everything else is a 422 that changes nothing.
 */
class OrderTransitionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The status each step needs.
     *
     * @var array<string, OrderStatus>
     */
    private const array STEPS = [
        'pickup' => OrderStatus::Pending,
        'receiver_confirmed' => OrderStatus::PickedUp,
        'deliver' => OrderStatus::Ongoing,
        'not_delivered' => OrderStatus::Ongoing,
    ];

    /**
     * @return array<string, array{string, OrderStatus}>
     */
    public static function forbiddenSteps(): array
    {
        $cases = [];

        foreach (self::STEPS as $step => $allowedFrom) {
            foreach (OrderStatus::cases() as $status) {
                if ($status !== $allowedFrom) {
                    $cases["{$step} from {$status->value}"] = [$step, $status];
                }
            }
        }

        return $cases;
    }

    private function runStep(string $step, Order $order, User $tayar): void
    {
        match ($step) {
            'pickup' => app(ConfirmPickupAction::class)->handle($order, $tayar, [UploadedFile::fake()->image('p.jpg')], 30.0, 31.0),
            'receiver_confirmed' => app(ConfirmReceiverAction::class)->handle($order, $tayar, 30.0, 31.0),
            'deliver' => app(DeliverOrderAction::class)->handle($order, $tayar, '123456', 30.0, 31.0),
            'not_delivered' => app(MarkOrderNotDeliveredAction::class)->handle($order, $tayar, '123456', OrderReason::factory()->notDelivered()->create(), null, 30.0, 31.0),
        };
    }

    #[DataProvider('forbiddenSteps')]
    public function test_a_step_from_the_wrong_status_is_refused_and_changes_nothing(string $step, OrderStatus $status): void
    {
        Storage::fake('local');
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $order = Order::factory()->acceptedBy($tayar)->create(['status' => $status]);

        try {
            $this->runStep($step, $order, User::query()->findOrFail($tayar->user_id));
            $this->fail('Expected the step to be refused.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('orders.wrong_step')], $exception->errors()['status']);
        }

        $this->assertSame($status, $order->refresh()->status);
        $this->assertSame(0, $order->code_attempts);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_the_steps_run_in_the_designs_order(): void
    {
        Storage::fake('local');
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $user = User::query()->findOrFail($tayar->user_id);
        $order = Order::factory()->acceptedBy($tayar)->create();

        $this->runStep('pickup', $order, $user);
        $this->runStep('receiver_confirmed', $order, $user);
        $codes = $this->sendReceiverCodes($order);
        app(DeliverOrderAction::class)->handle($order, $user, $codes['delivery'], 30.0, 31.0);

        $this->assertSame(
            [[OrderStatus::Pending, OrderStatus::PickedUp], [OrderStatus::PickedUp, OrderStatus::Ongoing], [OrderStatus::Ongoing, OrderStatus::Delivered]],
            $order->statusHistories()->get()->map(fn ($history): array => [$history->from_status, $history->to_status])->all(),
        );
    }
}
