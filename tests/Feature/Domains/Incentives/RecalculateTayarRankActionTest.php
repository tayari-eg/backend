<?php

namespace Tests\Feature\Domains\Incentives;

use App\Domains\Accounts\Actions\RecalculateBusinessRatingsAction;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\UserDevice;
use App\Domains\Catalog\Models\Category;
use App\Domains\Incentives\Actions\RecalculateTayarRankAction;
use App\Domains\Incentives\Enums\RankChangeSource;
use App\Domains\Incentives\Events\TayarRankAtRisk;
use App\Domains\Incentives\Events\TayarRankChanged;
use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Incentives\Models\TayarRankHistory;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderRating;
use App\Domains\Tayars\Models\TayarProfile;
use App\Jobs\SendPushToDevice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecalculateTayarRankActionTest extends TestCase
{
    use RefreshDatabase;

    private TayarRank $starter;

    private TayarRank $silver;

    private TayarRank $gold;

    protected function setUp(): void
    {
        parent::setUp();

        // Small thresholds keep the data small: the rules are the same as the seeded ranks.
        config(['tayari.ratings.min_ratings_for_average' => 2]);
        $this->starter = TayarRank::factory()->requiring(1, 0)->create(['code' => 'starter']);
        $this->silver = TayarRank::factory()->requiring(2, 2, '4.00')->create(['code' => 'silver']);
        $this->gold = TayarRank::factory()->requiring(3, 4, '4.50')->create(['code' => 'gold']);
    }

    /**
     * Delivered orders of the tayar, each rated by its business with the given stars and revealed the day before.
     *
     * @param  list<int|null>  $stars  null: delivered but not rated
     */
    private function deliver(TayarProfile $profile, array $stars, string $deliveredAt = '-3 days'): void
    {
        $sender = BusinessProfile::factory()->approved()->create()->user_id;
        $category = Category::factory()->create()->id;

        foreach ($stars as $star) {
            $order = $this->deliveredAt($profile, now()->modify($deliveredAt), $sender, $category);

            if ($star !== null) {
                OrderRating::factory()->forOrder($order)->revealedAt(now()->subDay())->create(['stars' => $star]);
            }
        }
    }

    private function deliveredAt(TayarProfile $profile, CarbonInterface $deliveredAt, ?int $senderId = null, ?int $categoryId = null): Order
    {
        return Order::factory()->onTheWayWith($profile)->forSender(User::query()->findOrFail($senderId ?? BusinessProfile::factory()->approved()->create()->user_id))->create([
            'category_id' => $categoryId ?? Category::factory()->create()->id,
            'status' => OrderStatus::Delivered,
            'delivered_at' => $deliveredAt,
        ]);
    }

    /**
     * A Cairo wall-clock moment in UTC, as the app stores times.
     */
    private function cairo(string $moment): CarbonImmutable
    {
        return CarbonImmutable::parse($moment, config('app.business_timezone'))->utc();
    }

    private function recalculate(TayarProfile $profile): TayarProfile
    {
        return app(RecalculateTayarRankAction::class)->handle($profile, now());
    }

    public function test_a_new_tayar_starts_at_the_lowest_rank_without_a_push(): void
    {
        Event::fake([TayarRankChanged::class, TayarRankAtRisk::class]);
        $profile = TayarProfile::factory()->approved()->create();

        $profile = $this->recalculate($profile);

        $this->assertSame($this->starter->id, $profile->rank_id);
        $this->assertSame([0, 0, null], [$profile->delivered_orders_30d, $profile->ratings_count_30d, $profile->rating_avg]);
        $history = TayarRankHistory::query()->sole();
        $this->assertSame([null, $this->starter->id, RankChangeSource::Auto], [$history->from_rank_id, $history->to_rank_id, $history->source]);
        Event::assertNotDispatched(TayarRankChanged::class);
    }

    public function test_a_tayar_who_meets_a_higher_rank_moves_up_the_same_night_with_a_history_row(): void
    {
        Event::fake([TayarRankChanged::class, TayarRankAtRisk::class]);
        $profile = $this->recalculate(TayarProfile::factory()->approved()->create());
        $this->deliver($profile, [5, 4, 5, 4]);

        $profile = $this->recalculate($profile);

        $this->assertSame($this->gold->id, $profile->rank_id);
        $this->assertSame([4, 4, '4.50'], [$profile->delivered_orders_30d, $profile->ratings_count_30d, $profile->rating_avg]);
        $this->assertSame([[null, $this->starter->id], [$this->starter->id, $this->gold->id]], TayarRankHistory::query()->orderBy('id')->get()->map(fn ($row): array => [$row->from_rank_id, $row->to_rank_id])->all());
        Event::assertDispatched(TayarRankChanged::class, fn (TayarRankChanged $event): bool => $event->from->is($this->starter) && $event->to->is($this->gold) && $event->isUp());
    }

    public function test_an_average_needs_the_minimum_ratings_and_only_revealed_ratings_in_the_window_count(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        // Two delivered orders, one rating: no average yet, so silver (which needs 4.00) is out of reach.
        $this->deliver($profile, [5, null]);
        // Delivered and rated too long ago, and a rating still in its window: neither counts.
        $this->deliver($profile, [5], '-40 days');
        OrderRating::query()->latest('id')->first()->forceFill(['revealed_at' => now()->subDays(31)])->save();
        $this->deliver($profile, [5]);
        OrderRating::query()->latest('id')->first()->forceFill(['revealed_at' => now()->addDay()])->save();
        // A not delivered order never counts.
        Order::factory()->onTheWayWith($profile)->create(['status' => OrderStatus::NotDelivered, 'not_delivered_at' => now()]);

        $profile = $this->recalculate($profile);

        $this->assertSame([3, 1, null], [$profile->delivered_orders_30d, $profile->ratings_count_30d, $profile->rating_avg]);
        $this->assertSame($this->starter->id, $profile->rank_id);
    }

    public function test_a_tayar_below_the_rank_keeps_it_for_the_grace_days_then_drops_to_the_rank_they_meet(): void
    {
        $this->freezeSecond();
        Event::fake([TayarRankChanged::class, TayarRankAtRisk::class]);
        $profile = TayarProfile::factory()->approved()->create();
        $profile->forceFill(['rank_id' => $this->gold->id])->save();
        $this->deliver($profile, [4, 4]);

        $profile = $this->recalculate($profile);

        $this->assertSame($this->gold->id, $profile->rank_id);
        $this->assertTrue($profile->rank_below_since->equalTo(now()));
        Event::assertDispatchedTimes(TayarRankAtRisk::class, 1);
        $dropsAt = now(config('app.business_timezone'))->startOfDay()->addDays(7)->utc();
        Event::assertDispatched(TayarRankAtRisk::class, fn (TayarRankAtRisk $event): bool => $event->rank->is($this->gold) && $event->dropsAt->equalTo($dropsAt));

        $this->travel(6)->days();
        $profile = $this->recalculate($profile);
        $this->assertSame($this->gold->id, $profile->rank_id);
        Event::assertDispatchedTimes(TayarRankAtRisk::class, 1);

        $this->travel(1)->days();
        $profile = $this->recalculate($profile);

        $this->assertSame($this->silver->id, $profile->rank_id);
        $this->assertNull($profile->rank_below_since);
        Event::assertDispatched(TayarRankChanged::class, fn (TayarRankChanged $event): bool => $event->to->is($this->silver) && ! $event->isUp());
        $this->assertSame(1, TayarRankHistory::query()->where('from_rank_id', $this->gold->id)->where('to_rank_id', $this->silver->id)->count());
    }

    public function test_meeting_the_rank_again_within_the_grace_days_clears_the_risk(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $profile->forceFill(['rank_id' => $this->silver->id, 'rank_below_since' => now()->subDays(3)])->save();
        $this->deliver($profile, [4, 5]);

        $profile = $this->recalculate($profile);

        $this->assertSame($this->silver->id, $profile->rank_id);
        $this->assertNull($profile->rank_below_since);
        $this->assertDatabaseCount('tayar_rank_histories', 0);
    }

    public function test_the_tayar_gets_a_push_when_the_rank_changes_and_when_it_is_at_risk(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $user = User::query()->findOrFail($profile->user_id);
        UserDevice::factory()->for($user)->create();
        $this->recalculate($profile);
        $this->deliver($profile, [5, 5]);
        Queue::fake();

        $this->recalculate($profile);
        $profile->forceFill(['rank_id' => $this->gold->id])->save();
        $this->recalculate($profile);

        $pushes = Queue::pushed(SendPushToDevice::class)->map(fn (SendPushToDevice $job): array => [$job->data['type'], $job->body])->values()->all();
        $dropsOn = now(config('app.business_timezone'))->addDays(7)->format('Y-m-d');
        $this->assertSame([
            ['rank_changed', __('push.rank_changed.up_body', ['rank' => $this->silver->getTranslation('name', 'ar')], 'ar')],
            ['rank_at_risk', __('push.rank_at_risk.body', ['rank' => $this->gold->getTranslation('name', 'ar'), 'date' => $dropsOn], 'ar')],
        ], $pushes);
    }

    public function test_night_eight_drops_the_rank_even_a_few_seconds_earlier_in_the_day_than_night_one(): void
    {
        Event::fake([TayarRankChanged::class, TayarRankAtRisk::class]);
        $this->travelTo($this->cairo('2026-11-10 03:00:10'));
        $profile = TayarProfile::factory()->approved()->create();
        $profile->forceFill(['rank_id' => $this->gold->id])->save();
        $this->deliver($profile, [4, 4]);

        $profile = $this->recalculate($profile);
        $this->assertSame($this->gold->id, $profile->rank_id);

        $this->travelTo($this->cairo('2026-11-16 03:00:02'));
        $this->assertSame($this->gold->id, $this->recalculate($profile)->rank_id);

        $this->travelTo($this->cairo('2026-11-17 03:00:02'));
        $this->assertSame($this->silver->id, $this->recalculate($profile)->rank_id);
    }

    public function test_the_grace_days_are_cairo_calendar_days_across_the_spring_clock_change(): void
    {
        $this->assertNotSame(CarbonImmutable::parse('2026-04-20 03:00', config('app.business_timezone'))->utcOffset(), CarbonImmutable::parse('2026-04-27 03:00', config('app.business_timezone'))->utcOffset());
        Event::fake([TayarRankChanged::class, TayarRankAtRisk::class]);
        $this->travelTo($this->cairo('2026-04-20 03:00:05'));
        $profile = TayarProfile::factory()->approved()->create();
        $profile->forceFill(['rank_id' => $this->gold->id])->save();
        $this->deliver($profile, [4, 4]);

        $this->recalculate($profile);
        Event::assertDispatched(TayarRankAtRisk::class, fn (TayarRankAtRisk $event): bool => $event->dropsAt->equalTo($this->cairo('2026-04-27 00:00')));

        $this->travelTo($this->cairo('2026-04-27 03:00:00'));
        $this->assertSame($this->silver->id, $this->recalculate($profile)->rank_id);
    }

    public function test_the_window_is_the_thirty_whole_cairo_days_before_the_run(): void
    {
        $this->travelTo($this->cairo('2026-11-20 03:00'));
        $profile = TayarProfile::factory()->approved()->create();
        // Between midnight and 03:00 Cairo on the window's first day: counted.
        $this->deliveredAt($profile, $this->cairo('2026-10-21 01:30'));
        $this->deliveredAt($profile, $this->cairo('2026-10-21 00:00'));
        $this->deliveredAt($profile, $this->cairo('2026-11-19 23:59'));
        // The day before the window, and today before the run: not counted.
        $this->deliveredAt($profile, $this->cairo('2026-10-20 23:59'));
        $this->deliveredAt($profile, $this->cairo('2026-11-20 01:30'));

        $this->assertSame(3, $this->recalculate($profile)->delivered_orders_30d);
    }

    public function test_business_averages_use_the_same_cairo_day_window(): void
    {
        config(['tayari.ratings.min_ratings_for_average' => 1]);
        $this->travelTo($this->cairo('2026-11-20 03:00'));
        $business = BusinessProfile::factory()->approved()->create();
        $profile = TayarProfile::factory()->approved()->create();
        foreach (['2026-10-21 01:30' => 4, '2026-11-20 01:30' => 1, '2026-10-20 23:59' => 1] as $revealedAt => $stars) {
            $order = $this->deliveredAt($profile, $this->cairo($revealedAt)->subDays(2), $business->user_id);
            OrderRating::factory()->forOrder($order)->byTayar()->revealedAt($this->cairo($revealedAt))->create(['stars' => $stars]);
        }

        app(RecalculateBusinessRatingsAction::class)->handle([$business->id], now());

        $this->assertSame(['4.00', 1], [$business->refresh()->rating_avg, $business->ratings_count_30d]);
    }

    public function test_a_tayar_who_is_not_approved_is_skipped(): void
    {
        $profile = TayarProfile::factory()->create();

        $profile = $this->recalculate($profile);

        $this->assertNull($profile->rank_id);
        $this->assertDatabaseCount('tayar_rank_histories', 0);
    }
}
