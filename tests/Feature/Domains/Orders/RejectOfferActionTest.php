<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Orders\Actions\RejectOfferAction;
use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class RejectOfferActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_last_live_offer_rejected_starts_the_next_round_without_the_rejecting_tayar(): void
    {
        $order = Order::factory()->create();
        $rejecting = $this->onlineTayar(30.0450, 31.2360);
        $offer = OrderOffer::factory()->forTayar($rejecting)->create(['order_id' => $order->id]);
        $next = $this->onlineTayar(30.0534, 31.2357);

        app(RejectOfferAction::class)->handle($offer);

        $this->assertSame(OfferStatus::Rejected, $offer->refresh()->status);
        $this->assertNotNull($offer->responded_at);
        $this->assertSame(
            [[$next->user_id, 2]],
            OrderOffer::query()->where('order_id', $order->id)->where('status', OfferStatus::Offered)->get()->map(fn (OrderOffer $offer): array => [$offer->tayar_id, $offer->round])->all(),
        );
    }

    public function test_the_round_goes_on_while_another_offer_is_live(): void
    {
        $order = Order::factory()->create();
        $offer = OrderOffer::factory()->forTayar($this->onlineTayar(30.0450, 31.2360))->create(['order_id' => $order->id]);
        OrderOffer::factory()->forTayar($this->onlineTayar(30.0534, 31.2357))->create(['order_id' => $order->id]);
        $this->onlineTayar(30.0460, 31.2370);

        app(RejectOfferAction::class)->handle($offer);

        $this->assertDatabaseCount('order_offers', 2);
    }

    public function test_rejecting_an_offer_that_is_no_longer_live_changes_nothing(): void
    {
        $order = Order::factory()->create();
        $offer = OrderOffer::factory()->forTayar($this->onlineTayar(30.0450, 31.2360))->overdue()->create(['order_id' => $order->id]);
        $withdrawn = OrderOffer::factory()->forTayar($this->onlineTayar(30.0460, 31.2370))->withStatus(OfferStatus::Withdrawn)->create(['order_id' => Order::factory()->cancelled()]);

        app(RejectOfferAction::class)->handle($offer);
        app(RejectOfferAction::class)->handle($withdrawn);

        $this->assertNotSame(OfferStatus::Rejected, $offer->refresh()->status);
        $this->assertSame(OfferStatus::Withdrawn, $withdrawn->refresh()->status);
    }

    public function test_offer_status_guards_every_single_offer_write(): void
    {
        $offer = OrderOffer::factory()->forTayar($this->onlineTayar(30.0450, 31.2360))->withStatus(OfferStatus::Rejected)->create();

        try {
            $offer->moveTo(OfferStatus::Accepted);
            $this->fail('Expected a final offer to refuse the move.');
        } catch (LogicException) {
            $this->assertSame(OfferStatus::Rejected, $offer->refresh()->status);
        }
    }

    public function test_it_locks_the_order_before_the_offer(): void
    {
        $offer = OrderOffer::factory()->forTayar($this->onlineTayar(30.0450, 31.2360))->create();
        $lockedTables = [];
        DB::listen(function (QueryExecuted $query) use (&$lockedTables): void {
            if (str_contains($query->sql, 'for update') && preg_match('/from [`"]?(\w+)[`"]?/', $query->sql, $match) === 1) {
                $lockedTables[] = $match[1];
            }
        });

        app(RejectOfferAction::class)->handle($offer);

        $this->assertSame(['orders', 'order_offers'], array_slice($lockedTables, 0, 2));
    }
}
