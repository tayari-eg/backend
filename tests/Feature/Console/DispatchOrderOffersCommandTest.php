<?php

namespace Tests\Feature\Console;

use App\Domains\Orders\Actions\CancelOrderAction;
use App\Domains\Orders\Actions\DispatchOrderOffersAction;
use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Pricing\Actions\CalculateTayarEarningAction;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DispatchOrderOffersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_starts_the_next_round_for_waiting_orders_and_cancels_the_ones_out_of_time(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $roundOver = Order::factory()->create();
        $expired = OrderOffer::factory()->forTayar($tayar)->overdue()->create(['order_id' => $roundOver->id]);
        $stillLive = Order::factory()->create();
        OrderOffer::factory()->forTayar($this->onlineTayar(30.0460, 31.2370))->create(['order_id' => $stillLive->id]);
        $outOfTime = Order::factory()->create(['price_accepted_at' => now()->subMinutes(config()->integer('tayari.matching.search_timeout_minutes') + 1)]);
        Order::factory()->cancelled()->create();

        $this->artisan('orders:dispatch-offers')
            ->expectsOutputToContain('2 offer(s) made.')
            ->assertSuccessful();

        $this->assertSame(OfferStatus::Expired, $expired->refresh()->status);
        // The waiting order goes to both online tayars: the one who let it expire, and the one busy only with an offer.
        $this->assertSame(2, OrderOffer::query()->where('order_id', $roundOver->id)->where('round', 2)->count());
        $this->assertSame(1, OrderOffer::query()->where('order_id', $stillLive->id)->count());
        $this->assertSame(OrderStatus::Cancelled, $outOfTime->refresh()->status);
    }

    public function test_an_order_that_fails_is_reported_and_the_next_order_still_gets_its_round(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $failing = Order::factory()->create();
        $next = Order::factory()->create();
        $this->app->instance(DispatchOrderOffersAction::class, new class($failing->id, app(CalculateTayarEarningAction::class), app(CancelOrderAction::class)) extends DispatchOrderOffersAction
        {
            public function __construct(private int $failingOrderId, CalculateTayarEarningAction $calculateTayarEarning, CancelOrderAction $cancelOrder)
            {
                parent::__construct($calculateTayarEarning, $cancelOrder);
            }

            public function handle(Order $order): array
            {
                if ($order->id === $this->failingOrderId) {
                    throw new RuntimeException('Matching failed for this order.');
                }

                return parent::handle($order);
            }
        });
        $reported = [];
        $this->app->make(ExceptionHandler::class)->reportable(function (RuntimeException $exception) use (&$reported): void {
            $reported[] = $exception->getMessage();
        });

        $this->artisan('orders:dispatch-offers')
            ->expectsOutputToContain('1 offer(s) made.')
            ->assertSuccessful();

        $this->assertSame(['Matching failed for this order.'], $reported);
        $this->assertSame(0, OrderOffer::query()->where('order_id', $failing->id)->count());
        $this->assertSame([$tayar->user_id], OrderOffer::query()->where('order_id', $next->id)->pluck('tayar_id')->all());
    }

    public function test_it_runs_every_minute(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => str_contains($event->command, 'orders:dispatch-offers'));

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
    }
}
