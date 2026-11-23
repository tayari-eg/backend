<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Orders\Actions\DeliverOrderAction;
use App\Domains\Orders\Actions\ResendReceiverCodesAction;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use SensitiveParameterValue;
use Tests\TestCase;

class DeliverOrderActionTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    private Order $order;

    /**
     * @var array{delivery: string, refusal: string}
     */
    private array $codes;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tayari.tracking.code_max_attempts' => 5, 'tayari.tracking.codes_valid_hours' => 24]);
        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
        $this->order = Order::factory()->onTheWayWith($this->tayar)->create();
        $this->codes = $this->sendReceiverCodes($this->order);
    }

    private function deliver(string $code): Order
    {
        return app(DeliverOrderAction::class)->handle($this->order, User::query()->findOrFail($this->tayar->user_id), $code, 30.06, 31.25);
    }

    private function assertCodeRefused(string $code, string $message): void
    {
        try {
            $this->deliver($code);
            $this->fail('Expected the code to be refused.');
        } catch (ValidationException $exception) {
            $this->assertSame([$message], $exception->errors()['code']);
        }

        $this->assertSame(OrderStatus::Ongoing, $this->order->refresh()->status);
    }

    private function wrongCode(): string
    {
        return $this->codes['delivery'] === '000000' ? '000001' : '000000';
    }

    public function test_the_delivery_code_delivers_and_clears_the_code_hashes(): void
    {
        $delivered = $this->deliver($this->codes['delivery']);

        $this->assertSame(OrderStatus::Delivered, $delivered->status);
        $this->assertNotNull($delivered->delivered_at);
        $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'delivery_code_hash' => null, 'refusal_code_hash' => null]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $this->order->id,
            'from_status' => OrderStatus::Ongoing->value,
            'to_status' => OrderStatus::Delivered->value,
            'changed_by' => $this->tayar->user_id,
            'lat' => '30.0600000',
            'lng' => '31.2500000',
        ]);
    }

    public function test_a_wrong_code_and_the_refusal_code_count_as_attempts_and_say_how_many_are_left(): void
    {
        $this->assertCodeRefused($this->wrongCode(), __('orders.code_wrong', ['left' => 4]));
        $this->assertCodeRefused($this->codes['refusal'], __('orders.code_wrong', ['left' => 3]));

        $this->assertSame(2, $this->order->refresh()->code_attempts);
    }

    public function test_five_wrong_codes_lock_the_codes_even_for_the_right_one_until_new_codes_are_sent(): void
    {
        foreach (range(4, 0) as $left) {
            $this->assertCodeRefused($this->wrongCode(), __('orders.code_wrong', ['left' => $left]));
        }

        $this->assertCodeRefused($this->codes['delivery'], __('orders.codes_locked'));
        $this->assertSame(5, $this->order->refresh()->code_attempts);

        $newCodes = $this->sendReceiverCodes($this->order);
        $this->assertSame(0, $this->order->refresh()->code_attempts);

        if ($newCodes['delivery'] !== $this->codes['delivery']) {
            $this->assertCodeRefused($this->codes['delivery'], __('orders.code_wrong', ['left' => 4]));
        }

        $this->assertSame(OrderStatus::Delivered, $this->deliver($newCodes['delivery'])->status);
    }

    public function test_expired_codes_are_refused_without_counting(): void
    {
        $this->travel(25)->hours();

        $this->assertCodeRefused($this->codes['delivery'], __('orders.codes_expired'));
        $this->assertSame(0, $this->order->refresh()->code_attempts);
    }

    public function test_codes_not_sent_yet_are_refused(): void
    {
        $this->order = Order::factory()->onTheWayWith($this->onlineTayar(30.0460, 31.2370))->create();
        $this->tayar = TayarProfile::query()->where('user_id', $this->order->tayar_id)->sole();

        $this->assertCodeRefused('123456', __('orders.codes_not_sent'));
    }

    public function test_resending_is_only_possible_while_the_goods_are_with_the_tayar(): void
    {
        $this->expectException(ValidationException::class);

        app(ResendReceiverCodesAction::class)->handle(Order::factory()->acceptedBy($this->tayar)->create());
    }

    public function test_a_picked_up_order_must_be_confirmed_on_the_way_first(): void
    {
        Order::query()->whereKey($this->order->id)->update(['status' => OrderStatus::PickedUp->value]);

        try {
            $this->deliver($this->codes['delivery']);
            $this->fail('Expected the step to be refused.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('orders.wrong_step')], $exception->errors()['status']);
        }

        $this->assertSame(0, $this->order->refresh()->code_attempts);
    }

    public function test_an_exception_inside_the_action_carries_no_plain_code_in_its_trace(): void
    {
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        DB::listen(function (QueryExecuted $query): void {
            if (str_contains($query->sql, 'for update')) {
                throw new RuntimeException('Lock failed.');
            }
        });

        try {
            app(DeliverOrderAction::class)->handle($this->order, User::query()->findOrFail($this->tayar->user_id), $this->codes['delivery'], 30.06, 31.25);
            $this->fail('Expected the lock to fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString($this->codes['delivery'], $exception->getTraceAsString());

            $actionFrame = collect($exception->getTrace())->firstWhere(fn (array $frame): bool => ($frame['class'] ?? null) === DeliverOrderAction::class && $frame['function'] === 'handle');
            $this->assertNotNull($actionFrame);
            $this->assertInstanceOf(SensitiveParameterValue::class, $actionFrame['args'][2]);

            $scalarArgs = collect($exception->getTrace())->flatMap(fn (array $frame): array => $frame['args'] ?? [])->filter(fn (mixed $arg): bool => is_scalar($arg));
            $this->assertNotContains($this->codes['delivery'], $scalarArgs->all());
        } finally {
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
        }
    }
}
